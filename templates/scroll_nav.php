<button id="dynamicScrollBtn" class="scroll-down" aria-label="التنقل في الصفحة">
    <svg id="scrollArrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
    </svg>
</button>
<style>
    #dynamicScrollBtn {
        position: fixed;
        bottom: 20px;
        right: 20px; /* يمكنك تغييرها إلى left: 20px إذا كنت تريد الزر في اليسار */
        z-index: 9999;
        background-color: var(--primary-color, #007bff); /* استخدم لون موقعك الأساسي */
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: background-color 0.3s ease;
    }

    #dynamicScrollBtn:hover {
        background-color: var(--secondary-color, #0056b3);
    }

    /* تأثير الدوران بناءً على الاتجاه */
    #scrollArrow {
        transition: transform 0.3s ease;
    }

    #dynamicScrollBtn.scroll-up #scrollArrow {
        transform: rotate(180deg); /* السهم يشير للأعلى */
    }

    #dynamicScrollBtn.scroll-down #scrollArrow {
        transform: rotate(0deg); /* السهم يشير للأسفل */
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const scrollBtn = document.getElementById("dynamicScrollBtn");

        // دالة تحديث اتجاه السهم بناءً على موقع التمرير
        function updateButtonDirection() {
            // إذا نزل المستخدم أكثر من 200 بكسل، اجعل السهم يشير للأعلى
            if (window.scrollY > 200) {
                scrollBtn.classList.remove("scroll-down");
                scrollBtn.classList.add("scroll-up");
            } else {
                // إذا كان في الأعلى، اجعل السهم يشير للأسفل
                scrollBtn.classList.remove("scroll-up");
                scrollBtn.classList.add("scroll-down");
            }
        }

        // استدعاء الدالة عند تحميل الصفحة وأثناء التمرير
        updateButtonDirection();
        window.addEventListener("scroll", updateButtonDirection);

        // وظيفة النقر على الزر
        scrollBtn.addEventListener("click", function() {
            if (scrollBtn.classList.contains("scroll-up")) {
                // الصعود لأعلى الصفحة بسلاسة
                window.scrollTo({ top: 0, behavior: "smooth" });
            } else {
                // النزول لأسفل الصفحة بسلاسة
                window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
            }
        });
    });
</script>