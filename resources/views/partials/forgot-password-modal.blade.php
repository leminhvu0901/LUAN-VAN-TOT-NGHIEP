<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <main class="relative w-full flex items-center justify-center overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <img class="w-full h-full object-cover blur-md brightness-90 opacity-40 scale-105"
                        alt="Khung nen mo cho quen mat khau"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9EcKwLn_2GnkhjupGT4ugujKT6FICa8H94-LabYa3JM_cGN6e6CGq0nyXLfT6ilz5TrAwsw3DcyG4QYMGHCKe1bl7EQ1sDGUNDgeT-p69rXllC9RSDvkWwenUbj5VUuGVJYkTNgaHL-ylBW-sDYbsscKFfXZKuoEVV5awYMqO5CLFJGVcR6uN6YJk0trWYgchScPa8ViDSWuJcWeJ97z4makdSnkwPCJm4gsOqy6T-Dr2CBzMcUAgZldGQ_JA9iRccMwxaYsnDYQ" />
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm"></div>
                </div>

                <div class="relative z-10 w-full max-w-[440px] px-4 py-8">
                    <section id="forgotPasswordCard"
                        class="bg-white shadow-lg rounded-xl p-6 md:p-8 border border-gray-200 flex flex-col gap-6">
                        <div class="flex justify-between items-center">
                            <h1 id="forgotPasswordModalLabel"
                                class="text-xl md:text-2xl font-semibold text-gray-900 tracking-tight">
                                Quên mật khẩu
                            </h1>
                            <button type="button"
                                class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors"
                                data-bs-dismiss="modal" aria-label="Close">
                                <span class="material-symbols-outlined text-gray-600">close</span>
                            </button>
                        </div>

                        <p class="text-gray-600 leading-relaxed">
                            Nhập email hoặc số điện thoại của bạn để nhận mã xác thực thiết lập lại mật khẩu.
                        </p>

                        <form class="flex flex-col gap-5" onsubmit="event.preventDefault();">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-semibold text-gray-800" for="recovery-contact">
                                    Email hoặc Số điện thoại
                                </label>
                                <div class="relative group">
                                    <input
                                        class="w-full h-14 px-4 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-all placeholder:text-gray-400"
                                        id="recovery-contact" placeholder="Nhập địa chỉ email hoặc số điện thoại"
                                        type="text" />
                                    <span
                                        class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-green-600 transition-colors">
                                        contact_mail
                                    </span>
                                </div>
                            </div>

                            <button
                                class="w-full h-14 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-2">
                                Gửi mã xác thực
                                <span class="material-symbols-outlined text-[20px]">send</span>
                            </button>
                        </form>

                        <div class="flex flex-col items-center gap-4 mt-2">
                            <a class="text-sm font-semibold text-green-700 hover:text-green-800 underline-offset-4 hover:underline transition-colors flex items-center gap-1"
                                href="#" data-bs-dismiss="modal">
                                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                                Quay lại đăng nhập
                            </a>
                            <div class="flex items-center gap-2 w-full">
                                <hr class="flex-grow border-gray-200" />
                                <span class="text-xs text-gray-500">HOAC</span>
                                <hr class="flex-grow border-gray-200" />
                            </div>
                            <p class="text-gray-600">
                                Chưa có tài khoản? <a class="text-green-700 font-semibold hover:underline" href="#">Đăng ký</a>
                            </p>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('forgotPasswordModal');
        const card = document.getElementById('forgotPasswordCard');

        if (!modal || !card) {
            return;
        }

        modal.addEventListener('mousemove', (e) => {
            if (window.innerWidth <= 768) {
                card.style.transform = '';
                return;
            }

            const rect = card.getBoundingClientRect();
            const xAxis = (rect.width / 2 - (e.clientX - rect.left)) / 100;
            const yAxis = (rect.height / 2 - (e.clientY - rect.top)) / 100;
            card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
        });

        modal.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    })();
</script>
