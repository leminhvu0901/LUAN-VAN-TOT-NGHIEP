/**
 * Đo sức chịu tải khi nhiều người CÙNG LÚC vào xem web (chỉ gửi GET, không tạo dữ liệu).
 *
 * Chạy bằng Node (không cần cài thêm gì):
 *   node tests/load/browse-ramp.js
 *   node tests/load/browse-ramp.js --url=https://happytea.up.railway.app --max=1000
 *
 * Cách hoạt động: tăng số người truy cập đồng thời theo từng bậc (50 → 200 → 500 → 1000).
 * Có "phanh tự động" — dừng ngay khi tỉ lệ lỗi hoặc thời gian phản hồi vượt ngưỡng, để không
 * làm sập server quá lâu.
 */
import http from 'node:http';
import https from 'node:https';

// ---- Tham số dòng lệnh ----
const args = Object.fromEntries(
    process.argv.slice(2).map((a) => {
        const [k, v] = a.replace(/^--/, '').split('=');
        return [k, v ?? true];
    })
);

const BASE_URL = args.url || 'http://127.0.0.1:8000';
const MAX_VUS = parseInt(args.max || '1000', 10);
const PATHS = ['/', '/products'];

// Các bậc tải: [số người đồng thời, số giây giữ ở mức đó]
const STAGES = [
    [Math.min(50, MAX_VUS), 20],
    [Math.min(200, MAX_VUS), 20],
    [Math.min(500, MAX_VUS), 20],
    [MAX_VUS, 30],
].filter(([vus], i, arr) => i === 0 || vus > arr[i - 1][0]);

// ---- Ngưỡng phanh tự động ----
const ABORT_ERROR_RATE = 0.20;   // quá 20% request lỗi
const ABORT_P95_MS = 10000;      // 95% số request chậm hơn 10 giây
const REQUEST_TIMEOUT_MS = 20000;

const isHttps = BASE_URL.startsWith('https');
const agent = new (isHttps ? https.Agent : http.Agent)({
    keepAlive: true,
    maxSockets: Infinity,
});

function percentile(sorted, p) {
    if (!sorted.length) return 0;
    const idx = Math.min(sorted.length - 1, Math.floor((p / 100) * sorted.length));
    return sorted[idx];
}

function request(path) {
    return new Promise((resolve) => {
        const started = Date.now();
        const url = new URL(path, BASE_URL);
        const lib = url.protocol === 'https:' ? https : http;

        const req = lib.get(
            url,
            { agent, timeout: REQUEST_TIMEOUT_MS, headers: { 'User-Agent': 'load-test/1.0' } },
            (res) => {
                res.resume(); // bỏ nội dung, chỉ quan tâm thời gian và mã trạng thái
                res.on('end', () => resolve({ status: res.statusCode, ms: Date.now() - started }));
            }
        );

        req.on('timeout', () => { req.destroy(); resolve({ status: 0, ms: Date.now() - started, err: 'timeout' }); });
        req.on('error', (e) => resolve({ status: 0, ms: Date.now() - started, err: e.code || 'error' }));
    });
}

/** Chạy một bậc tải: giữ đúng `vus` request chạy song song trong `seconds` giây. */
async function runStage(vus, seconds) {
    const endAt = Date.now() + seconds * 1000;
    const durations = [];
    const statuses = {};
    let done = 0, failed = 0, stop = false;
    let pathIndex = 0;

    async function worker() {
        while (Date.now() < endAt && !stop) {
            const path = PATHS[pathIndex++ % PATHS.length];
            const r = await request(path);
            durations.push(r.ms);
            const key = r.err ? r.err : String(r.status);
            statuses[key] = (statuses[key] || 0) + 1;
            done++;
            if (r.err || r.status >= 500) failed++;
        }
    }

    // Bộ đếm giờ kiểm tra ngưỡng phanh mỗi 2 giây
    const watchdog = setInterval(() => {
        if (done < 30) return; // chờ đủ mẫu rồi mới kết luận
        const rate = failed / done;
        const p95 = percentile([...durations].sort((a, b) => a - b), 95);
        if (rate > ABORT_ERROR_RATE || p95 > ABORT_P95_MS) {
            stop = true;
        }
    }, 2000);

    const started = Date.now();
    await Promise.all(Array.from({ length: vus }, worker));
    clearInterval(watchdog);

    const elapsed = (Date.now() - started) / 1000;
    const sorted = [...durations].sort((a, b) => a - b);

    return {
        vus, done, failed, stop, elapsed,
        rps: done / elapsed,
        errorRate: done ? failed / done : 0,
        p50: percentile(sorted, 50),
        p95: percentile(sorted, 95),
        p99: percentile(sorted, 99),
        max: sorted[sorted.length - 1] || 0,
        statuses,
    };
}

(async () => {
    console.log(`\nMục tiêu : ${BASE_URL}`);
    console.log(`Đường dẫn: ${PATHS.join(', ')}`);
    console.log(`Các bậc  : ${STAGES.map(([v, s]) => `${v} người/${s}s`).join('  →  ')}`);
    console.log(`Phanh    : dừng khi lỗi > ${ABORT_ERROR_RATE * 100}% hoặc p95 > ${ABORT_P95_MS / 1000}s\n`);
    console.log('  Người   Request   RPS    p50      p95      p99      Lỗi     Mã trạng thái');
    console.log('  ' + '-'.repeat(76));

    for (const [vus, seconds] of STAGES) {
        const r = await runStage(vus, seconds);
        const codes = Object.entries(r.statuses).map(([k, v]) => `${k}:${v}`).join(' ');
        console.log(
            `  ${String(r.vus).padStart(5)}  ${String(r.done).padStart(7)}  ` +
            `${r.rps.toFixed(0).padStart(4)}  ${(r.p50 + 'ms').padStart(7)}  ` +
            `${(r.p95 + 'ms').padStart(7)}  ${(r.p99 + 'ms').padStart(7)}  ` +
            `${(r.errorRate * 100).toFixed(1).padStart(5)}%  ${codes}`
        );

        if (r.stop) {
            console.log(`\n  >> ĐÃ DỪNG ở mức ${vus} người: vượt ngưỡng an toàn (lỗi ${(r.errorRate * 100).toFixed(1)}%, p95 ${r.p95}ms).`);
            console.log('  >> Đây chính là giới hạn chịu tải của hệ thống ở cấu hình hiện tại.\n');
            process.exit(0);
        }

        // Nghỉ giữa 2 bậc cho server hồi lại
        await new Promise((r) => setTimeout(r, 3000));
    }

    console.log('\n  >> Chạy hết các bậc mà không vượt ngưỡng — hệ thống trụ được mức tải này.\n');
})();
