<?php
?>
<style>
    #loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: #1a2a44;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;

        transition: opacity 0.5s ease-out;
        opacity: 1;
    }

    #loader-overlay.loader-hidden {
        opacity: 0;
        pointer-events: none;
    }

    .loader {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        position: relative;
        animation: rotate 1s linear infinite;
    }
    .loader::before , .loader::after {
        content: "";
        box-sizing: border-box;
        position: absolute;
        inset: 0px;
        border-radius: 50%;
        border: 5px solid #FFF;
        animation: prixClipFix 2s linear infinite ;
    }
    .loader::after{
        inset: 8px;
        transform: rotate3d(90, 90, 0, 180deg );
        border-color: #FF3D00;
    }

    @keyframes rotate {
        0%   {transform: rotate(0deg)}
        100% {transform: rotate(360deg)}
    }

    @keyframes prixClipFix {
        0%   {clip-path:polygon(50% 50%,0 0,0 0,0 0,0 0,0 0)}
        50%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 0,100% 0,100% 0)}
        75%, 100%  {clip-path:polygon(50% 50%,0 0,100% 0,100% 100%,100% 100%,100% 100%)}
    }
</style>

<div id="loader-overlay">
    <span class="loader"></span>
</div>

<script>
    // Bọc trong hàm để tránh xung đột biến
    (function() {
        // Lắng nghe sự kiện DOMContentLoaded để đảm bảo an toàn,
        // mặc dù include ngay sau body thường là đủ nhanh.
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tham chiếu đến overlay
            const loaderOverlay = document.getElementById('loader-overlay');

            // Nếu không tìm thấy loader (ví dụ: include nhầm chỗ), thì thoát
            if (!loaderOverlay) {
                console.warn("Loader overlay not found. Make sure loader.php is included correctly.");
                return;
            }

            // Biến cờ để theo dõi 2 điều kiện
            let pageLoaded = false;
            let minTimePassed = false;
            const minLoadTime = 1500; // 1.5 giây

            // Hàm để kiểm tra và ẩn loader
            function tryHideLoader() {
                // Chỉ ẩn khi CẢ HAI điều kiện đều đúng
                if (pageLoaded && minTimePassed) {
                    loaderOverlay.classList.add('loader-hidden');
                    
                    setTimeout(() => {
                        if (loaderOverlay) {
                             loaderOverlay.style.display = 'none';
                        }
                    }, 500);
                }
            }

            // 1. Theo dõi sự kiện trang tải xong (window.onload)
            window.onload = function() {
                pageLoaded = true;
                tryHideLoader();
            };

            // 2. Theo dõi sự kiện thời gian tối thiểu
            setTimeout(function() {
                minTimePassed = true;
                tryHideLoader();
            }, minLoadTime);
        });
    })();
</script>
