<?php
session_start();

// Nếu đã đăng nhập thì chuyển đúng trang
if (isset($_SESSION['user'])) {

    switch ($_SESSION['user']['role']) {

        case 'admin':
            header("Location: admin/dashboard.php");
            exit;

        case 'staff':
            header("Location: staff/dashboard.php");
            exit;

        case 'driver':
            header("Location: driver/dashboard.php");
            exit;

        case 'customer':
            header("Location: customer/dashboard.php");
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>FASTGO - Trang chủ</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
   

<header>

    <div class="logo">
    <img src="assets/img/logo.png" alt="FASTGO Logo">
</div>
    <div class="right">

        <a href="javascript:void(0)" onclick="openLoginModal()">
            <i class="fa-solid fa-right-to-bracket"></i>
            Đăng nhập
        </a>

        <a href="auth/register.php">
            <i class="fa-solid fa-user-plus"></i>
            Đăng ký
        </a>

    </div> <div class="overlay"></div>

</header>

<div class="content">

<div class="main">

<h1>Chào mừng đến với FASTGO</h1>

<p>Giao hàng nhanh - An toàn - Tiện lợi</p>

<div class="action-buttons">

<a class="btn-main" href="auth/login.php">
<i class="fa-solid fa-truck-fast"></i>
Tạo đơn ngay
</a>

<a class="btn-main" href="auth/login.php">
<i class="fa-solid fa-box"></i>
Đơn hàng
</a>



</div>

</div>

</div>

<footer>

© <?php echo date("Y"); ?> FASTGO

</footer>
<div id="loginModal" class="login-modal">

    <div class="login-box">

        <span class="close-btn" onclick="closeLoginModal()">&times;</span>

        <h1>FASTGO</h1>

        <p>Hệ thống quản lý vận chuyển và giao hàng</p>

        <a href="auth/login.php" class="login-card customer">
            <i class="fa-solid fa-user"></i>
            Khách hàng
        </a>

        <a href="auth/login-nhanvien.php" class="login-card staff">
            <i class="fa-solid fa-user-tie"></i>
            Nhân viên
        </a>

        <a href="auth/login-admin.php" class="login-card admin">
            <i class="fa-solid fa-user-shield"></i>
            Quản trị viên
        </a>

        <div class="login-footer">
            Admin • Staff • Driver • Customer
        </div>

    </div>

</div>
<script>

function openLoginModal(){

    document.getElementById("loginModal").style.display="flex";
}

function closeLoginModal(){

    document.getElementById("loginModal").style.display="none";
}

window.onclick=function(e){

    let modal=document.getElementById("loginModal");

    if(e.target==modal){

        modal.style.display="none";
    }

}

</script>
</body>
</html>
<style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;}
body{
    min-height:100vh;
    overflow:hidden;

    background:url("assets/img/nen.png") center center no-repeat;
    background-size:cover;
    background-attachment:fixed;
}

/*================ HEADER ================*/
/*================ HEADER ================*/
header{
    
    top:0;
    left:0;
    width:100%;
    height:70px;

    display:flex;
    justify-content:space-between;
    align-items:center;

      padding:0 30px 0 50px;

    background:rgba(255,255,255,.45);
    backdrop-filter:blur(15px);

    border-bottom:1px solid rgba(255,255,255,.3);

    box-shadow:0 5px 20px rgba(0,0,0,.1);

     position:fixed;
    z-index:9999;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
}
.logo img{
    height: 70px;
    object-fit: contain;
}

.right{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:15px;
    margin-left:auto;
}

.right a{

    display:flex;
    align-items:center;
    justify-content:center;

    min-width:130px;

    height:45px;

    text-decoration:none;

    border-radius:30px;

    font-weight:600;

    transition:.3s;
}

.right a:first-child{
    border:1px solid #00b14f;
    color:#00b14f;
    background:rgba(255,255,255,.6);
}

.right a:last-child{
    background:#00b14f;
    color:#fff;
}

.right a:first-child:hover{
    background:#00b14f;
    color:#fff;
    transform:translateY(-2px);
}

.right a:last-child:hover{
    background:#009944;
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(0,177,79,.25);
}

.right a i{
    font-size:15px;
}
/*================ DROPDOWN ================*/
.dropdown{ position:relative;}
.dropdown-content{position:absolute;top:55px;right:0;width:250px;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,.18);display:none;}
.dropdown:hover .dropdown-content{display:block;}
.dropdown-content a{display:block;padding:15px 18px;color:#444;text-decoration:none;transition:.3s;}
.dropdown-content a:hover{background:#00b14f;color:#fff;}
/*================ CONTENT ================*/
.content{

    min-height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;

    padding-top:90px;

    
 position:relative;
    z-index:2;

}
/*.content::before{content:"";position:absolute;width:650px;height:650px;background:#00b14f;border-radius:50%;filter:blur(170px);opacity:.18;
top:-180px;
right:-180px;}
.content::after{ content:"";position:absolute;width:450px;height:450px;background:#007bff;border-radius:50%;filter:blur(150px);opacity:.12;bottom:-150px;left:-150px;}*/
.main{

    position:relative;

    width:950px;

    padding:70px;

    border-radius:30px;

    background:rgba(255,255,255,.78);

    backdrop-filter:blur(10px);

    box-shadow:0 20px 60px rgba(0,0,0,.18);

    text-align:center;

    opacity:0;

    transform:translateY(80px);

    animation:showContent .9s ease forwards;

    animation-delay:1.2s;

}
.main h1{font-size:52px;color:#00b14f;margin-bottom:18px;}
.main p{font-size:21px;color:#666;margin-bottom:45px;}
/*================ BUTTON ================*/
.action-buttons{display:flex;justify-content:center;gap:22px;flex-wrap:wrap;}
.btn-main{
    width:240px;
    padding:22px;
    text-decoration:none;

  background:linear-gradient(135deg,#F6A94B,#E98A15);

    color:#fff;

    border-radius:18px;

    font-size:20px;

    font-weight:700;

    transition:.35s;

      box-shadow:0 12px 25px rgba(233,138,21,.25);
}
.btn-main i{display:block;font-size:36px;margin-bottom:12px;}
.btn-main:hover{

    transform:translateY(-6px);

      background:linear-gradient(135deg,#F0A13C,#D97A0B);

     box-shadow:0 18px 35px rgba(233,138,21,.3);
}
/*================ FEATURES ================*/
.features{display:grid;grid-template-columns:repeat(3,1fr);gap:25px;margin-top:55px;}
.feature{background:#fff;border-radius:18px;padding:28px;box-shadow:0 10px 25px rgba(0,0,0,.08);transition:.35s;}
.feature:hover{transform:translateY(-8px);}
.feature i{font-size:40px;color:#00b14f;margin-bottom:18px;}
.feature h3{margin-bottom:12px;color:#222;}
.feature p{font-size:15px;color:#666;margin:0;}
/*================ FOOTER ================*/
footer{ background:#111827;color:#fff;text-align:center;padding:22px;}
/*================ MOBILE ================*/
@media(max-width:900px){
header{padding:0 20px;}
.main{width:95%;padding:40px 25px;}
.main h1{font-size:38px;}
.action-buttons{

    display:flex;

    justify-content:center;

    align-items:center;

    gap:35px;

    margin-top:40px;
}
.btn-main{width:100%;max-width:330px;}
.features{grid-template-columns:1fr;}}
.login-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(6px);justify-content:center;align-items:center;z-index:9999;}
.login-box{width:430px;background:#fff;border-radius:22px;padding:35px;text-align:center;animation:zoom .25s ease;}
.login-box h1{color:#2563eb;font-size:50px;margin-bottom:10px;}
.login-box p{color:#666;margin-bottom:35px;}
.login-card{display:block;text-decoration:none;color:#fff;font-size:24px;font-weight:bold;padding:22px;margin:18px 0;border-radius:18px;transition:.3s;}
.login-card:hover{transform:translateY(-5px);box-shadow:0 15px 25px rgba(0,0,0,.18);}
.customer{background:#f59e0b;}
.staff{background:#22c55e;}
.admin{background:#7c3aed;}
.login-footer{ margin-top:30px;color:#999;}
.close-btn{float:right;font-size:32px;cursor:pointer;color:#888;}
.close-btn:hover{color:red;}
@keyframes zoom{from{transform:scale(.8);opacity:0;}
to{transform:scale(1);opacity:1;}
}
.overlay{
    position:fixed;
    inset:0;
    background:rgba(255,255,255,0);
    animation:fadeOverlay 1.3s forwards;
    z-index:1;
    pointer-events:none;
}
@keyframes fadeOverlay{
    0%{background:rgba(255,255,255,0);}
    100%{
    background:rgba(255,255,255,.35);
    }
}

@keyframes showContent{
    from{ opacity:0; transform:translateY(90px);}
to{opacity:1;transform:translateY(0);}
}
</style>