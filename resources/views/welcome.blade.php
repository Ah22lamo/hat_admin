<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HAT Trading</title>

    <link rel="icon" type="image/png" href="{{ asset('images/hat-logo.png') }}">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background:
                radial-gradient(circle at 85% 15%, rgba(35,139,149,.16), transparent 30%),
                linear-gradient(135deg, #f7fbfc 0%, #eef7f8 100%);
            color: #17282c;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            width: min(1100px, 100%);
            min-height: 600px;
            background: rgba(255,255,255,.94);
            border: 1px solid #e1e9eb;
            border-radius: 28px;
            box-shadow: 0 25px 70px rgba(23, 105, 112, .12);
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            overflow: hidden;
        }

        .visual {
            background: linear-gradient(145deg, #176c74, #238b95);
            color: white;
            padding: 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .visual::before,
        .visual::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.15);
        }

        .visual::before {
            width: 420px;
            height: 420px;
            top: -180px;
            right: -160px;
        }

        .visual::after {
            width: 300px;
            height: 300px;
            bottom: -150px;
            left: -120px;
        }

        .logo {
            width: 105px;
            height: 105px;
            object-fit: contain;
            background: white;
            padding: 14px;
            border-radius: 22px;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .visual h1 {
            font-size: 46px;
            margin: 0 0 15px;
            position: relative;
            z-index: 1;
        }

        .visual p {
            font-size: 18px;
            line-height: 1.9;
            opacity: .9;
            max-width: 430px;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .badge {
            display: inline-flex;
            width: fit-content;
            padding: 8px 15px;
            border-radius: 30px;
            background: #eaf6f7;
            color: #176c74;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .content h2 {
            font-size: 34px;
            margin: 0 0 15px;
        }

        .content p {
            color: #506267;
            line-height: 1.9;
            font-size: 16px;
            margin-bottom: 35px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            background: #238b95;
            color: white;
            padding: 16px 28px;
            border-radius: 14px;
            font-size: 17px;
            font-weight: bold;
            transition: .25s ease;
            box-shadow: 0 10px 25px rgba(35,139,149,.22);
        }

        .button:hover {
            background: #176c74;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(35,139,149,.28);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 30px;
        }

        .feature {
            padding: 16px;
            border: 1px solid #e1e9eb;
            border-radius: 14px;
            text-align: center;
            font-size: 13px;
            color: #506267;
        }

        @media (max-width: 800px) {
            .card {
                grid-template-columns: 1fr;
            }

            .visual {
                padding: 40px;
            }

            .content {
                padding: 40px;
            }

            .visual h1 {
                font-size: 36px;
            }
        }
    </style>
</head>

<body>

<div class="page">
    <main class="card">

        <section class="visual">
            <img
                src="{{ asset('images/hat-logo.png') }}"
                alt="HAT Logo"
                class="logo"
            >

            <h1>HAT Trading</h1>

            <p>
                نظام HAT لإدارة المنتجات والعمليات التجارية
                بطريقة منظمة، حديثة، وقابلة للتوسع.
            </p>
        </section>

        <section class="content">

            <span class="badge">HAT ADMINISTRATION SYSTEM</span>

            <h2>مرحباً بك في HAT</h2>

            <p>
                منصة الإدارة الداخلية الخاصة بشركة
                Hamed Alyosofi for Trading.
                من هنا يمكن للموظفين المصرح لهم إدارة المنتجات
                والتصنيفات والبيانات والعمليات المرتبطة بالنظام.
            </p>

            <a href="{{ url('/admin') }}" class="button">
                الدخول إلى لوحة الإدارة
            </a>

            <div class="features">
                <div class="feature">إدارة المنتجات</div>
                <div class="feature">التصنيفات</div>
                <div class="feature">التقارير</div>
            </div>

        </section>

    </main>
</div>

</body>
</html>