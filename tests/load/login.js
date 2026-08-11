/**
 * Kịch bản đo hiệu năng ĐĂNG NHẬP bằng k6 — mô phỏng 1000 người đăng nhập cùng lúc.
 *
 * Chuẩn bị:
 *   1) php artisan db:seed --class=LoadTestUserSeeder      (tạo 1000 tài khoản ảo)
 *   2) Cài k6: https://k6.io/docs/get-started/installation/
 *
 * Chạy (mặc định bắn vào máy local):
 *   k6 run tests/load/login.js
 *
 * Bắn vào địa chỉ khác:
 *   k6 run -e BASE_URL=https://happytea.up.railway.app tests/load/login.js
 *
 * Đổi số người dùng tối đa:
 *   k6 run -e VUS=200 tests/load/login.js
 *
 * CẢNH BÁO: đừng bắn 1000 người vào server thật đang chạy cho khách xem. Xem phần
 * ghi chú cuối file để hiểu vì sao.
 */
import http from 'k6/http';
import { check, group } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const MAX_VUS = parseInt(__ENV.VUS || '1000', 10);
const TOTAL_ACCOUNTS = parseInt(__ENV.ACCOUNTS || '1000', 10);

// Số liệu tự đặt để báo cáo cho dễ đọc
const loginDuration = new Trend('thoi_gian_dang_nhap', true);
const loginSuccess = new Rate('ty_le_dang_nhap_thanh_cong');

export const options = {
  stages: [
    { duration: '30s', target: Math.round(MAX_VUS * 0.2) }, // khởi động nhẹ
    { duration: '1m', target: MAX_VUS },                    // tăng dần tới mức tối đa
    { duration: '1m', target: MAX_VUS },                    // giữ tải để xem hệ thống chịu được không
    { duration: '30s', target: 0 },                         // hạ tải
  ],
  thresholds: {
    // Ngưỡng để k6 tự kết luận đạt/không đạt
    ty_le_dang_nhap_thanh_cong: ['rate>0.95'],   // trên 95% lượt đăng nhập thành công
    thoi_gian_dang_nhap: ['p(95)<3000'],         // 95% số lượt phản hồi dưới 3 giây
    http_req_failed: ['rate<0.05'],              // dưới 5% request lỗi
  },
};

export default function () {
  // Mỗi người dùng ảo dùng một tài khoản riêng để giống thực tế
  // (nhiều người khác nhau đăng nhập, không phải một người bấm 1000 lần).
  const index = ((__VU - 1) % TOTAL_ACCOUNTS) + 1;

  group('Đăng nhập', function () {
    const res = http.post(
      `${BASE_URL}/login`,
      {
        email: `loadtest${index}@test.local`,
        password: 'loadtest123',
      },
      {
        // Không tự đi theo chuyển hướng: chỉ đo đúng thời gian xử lý đăng nhập,
        // không tính thêm thời gian tải trang chủ phía sau.
        redirects: 0,
        tags: { name: 'POST /login' },
      }
    );

    // Đăng nhập thành công thì Laravel trả 302 (chuyển hướng), sai thì trả về lại trang login
    const ok = res.status === 302;

    loginDuration.add(res.timings.duration);
    loginSuccess.add(ok);

    check(res, {
      'tra ve 302 (dang nhap thanh cong)': (r) => r.status === 302,
      'khong bi loi may chu (5xx)': (r) => r.status < 500,
    });
  });
}

/**
 * GHI CHÚ ĐỌC KẾT QUẢ — 3 nút thắt của dự án này
 *
 * 1. Băm mật khẩu (BCRYPT_ROUNDS=12)
 *    Bcrypt cố tình chạy chậm để chống dò mật khẩu. Ở mức 12, mỗi lần đăng nhập tốn
 *    khoảng 0.2-0.4 giây CPU. 1000 người đăng nhập cùng lúc = 1000 lần băm, đây gần
 *    như chắc chắn là thứ làm chậm nhất. Đây là ĐÁNH ĐỔI CÓ CHỦ Ý giữa bảo mật và tốc
 *    độ, không phải lỗi — nêu được điều này trong báo cáo là một điểm cộng.
 *
 * 2. Session lưu trong database (SESSION_DRIVER=database)
 *    Mỗi lượt đăng nhập ghi thêm một dòng vào bảng sessions, và MỌI request sau đó đều
 *    đọc/ghi bảng này. Chuyển sang Redis sẽ giảm tải đáng kể cho MySQL.
 *
 * 3. Không có giới hạn số lần đăng nhập sai
 *    Route POST /login hiện không gắn middleware throttle nào, nên ngoài chuyện chịu tải
 *    kém, hệ thống còn có thể bị dò mật khẩu tự động. Khắc phục: thêm ->middleware('throttle:5,1')
 *    cho route đăng nhập (cho phép 5 lần/phút trên mỗi IP).
 */
