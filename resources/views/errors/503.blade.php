<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>系統維護中</title>
    <style>
        :root {
            --brand-color: #F05340;
            --bg-color: #f9f9f9;
            --text-color: #333;
            --subtext-color: #666;
            --gear-color: var(--brand-color);
            --button-border: #ccc;
        }

        [data-theme="dark"] {
            --bg-color: #1f1f1f;
            --text-color: #fff;
            --subtext-color: #aaa;
            --gear-color: var(--brand-color);
            --button-border: #444;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            transition: all 0.3s ease;
        }

        .gear {
            font-size: 80px;
            color: var(--gear-color);
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        h1 {
            font-size: 3rem;
            margin: 1rem 0 0.5rem;
            color: var(--brand-color);
        }

        p {
            font-size: 1.25rem;
            color: var(--subtext-color);
            max-width: 400px;
            margin-bottom: 1rem;
        }

        #countdown {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: transparent;
            border: 2px solid var(--button-border);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            color: var(--text-color);
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .theme-toggle:hover {
            background-color: var(--brand-color);
            color: #fff;
            border-color: var(--brand-color);
        }

        footer {
            position: absolute;
            bottom: 1rem;
            font-size: 0.85rem;
            color: var(--subtext-color);
        }
    </style>
</head>
<body data-theme="light">
<button class="theme-toggle" onclick="toggleTheme()">🌞/🌙 切換主題</button>

<div class="gear">⚙️</div>
<h1>系統維護中</h1>
<p>我們正在進行系統升級，請耐心等候。</p>
<div id="countdown">剩餘時間載入中...</div>
<footer>© 2025 Your Company Name</footer>

<script>
    // 設定維護結束時間為現在起 24 小時後
    const maintenanceEnd = new Date(Date.now() + 24 * 60 * 60 * 1000).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = maintenanceEnd - now;

        if (distance <= 0) {
            document.getElementById("countdown").innerText = "維護已完成，請重新整理頁面";
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("countdown").innerText =
            `預計剩餘 ${hours} 小時 ${minutes} 分 ${seconds} 秒`;
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    // 主題切換功能
    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute("data-theme");
        const newTheme = currentTheme === "dark" ? "light" : "dark";
        body.setAttribute("data-theme", newTheme);
    }
</script>
</body>
</html>
