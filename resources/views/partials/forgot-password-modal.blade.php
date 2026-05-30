<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered flex justify-center">
        <div class="modal-content bg-transparent border-0 shadow-none flex justify-center items-center w-full">

            <div id="forgotPasswordCard" class="bg-white rounded-[20px] shadow-lg w-full max-w-[360px] p-6 relative">

                <div class="flex items-center justify-between mb-6 relative">
                    <button type="button" class="text-gray-500 hover:text-gray-800 absolute left-0 flex items-center" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h1 class="text-[1.15rem] font-bold text-gray-800 w-full text-center m-0">
                        Quên mật khẩu
                    </h1>
                </div>

                <div class="flex justify-center mb-5">
                    <div class="w-14 h-14 bg-[#cbf0d9] rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#364b3e" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5v-2m0 0V11m0 2.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                        </svg>
                    </div>
                </div>

                <p class="text-center text-[13px] text-gray-600 mb-6 leading-relaxed px-2">
                    Vui lòng nhập email hoặc số điện thoại để nhận mã khôi phục mật khẩu.
                </p>

                <form class="w-full flex flex-col" onsubmit="event.preventDefault();">
                    <div class="mb-5">
                        <label class="block text-[13px] font-bold text-gray-700 mb-1.5 text-left">
                            Email hoặc Số điện thoại
                        </label>
                        <div class="relative">
                            <input type="text" placeholder="Nhập email hoặc số điện thoại"
                                class="w-full h-[42px] px-3 pr-10 border border-gray-200 rounded-lg text-[13px] focus:outline-none focus:border-[#00a63e] focus:ring-1 focus:ring-[#00a63e] transition-colors bg-[#fdfdfd] placeholder-gray-400">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <button class="w-full h-[42px] bg-[#06a12b] hover:bg-[#058a24] text-white font-bold text-[13px] rounded-full flex items-center justify-center gap-2 transition-all shadow-sm">
                        Gửi mã xác nhận
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                        </svg>
                    </button>
                </form>

                <div class="mt-6 flex flex-col items-center">
                    <a href="#" class="text-[13px] text-gray-700 font-medium border-b border-gray-400 border-dashed pb-[1px] hover:text-black mb-5" data-bs-dismiss="modal">
                        Quay lại đăng nhập
                    </a>

                    <hr class="w-full border-t border-gray-100 mb-4">

                    <p class="text-[13px] text-gray-500 m-0">
                        Chưa có tài khoản? <a href="#" class="text-[#06a12b] font-bold hover:underline">Đăng ký</a>
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>
