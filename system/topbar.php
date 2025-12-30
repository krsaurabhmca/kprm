<div class="main">
<nav class="navbar navbar-expand navbar-light navbar-bg">
    
<?php if($user_type =='ADMIN' or $user_type =='DEV'){ ?>
    <a class="sidebar-toggle js-sidebar-toggle">
        <i class="hamburger align-self-center"></i>
    </a>
    <?php } ?>
<?php if($user_type =='ADMIN' or $user_type =='DEV'){ ?>
    <!--<form class=" d-sm-inline-block" action='op_search' method='post'>-->
    <!--    <div class="input-group input-group-navbar">-->
    <!--        <input type="text" class="form-control" placeholder="Search Member" aria-label="Search" id='search_text' name='search_term' required>-->
    <!--        <button class="btn" type="button" onclick='submit()'>-->
    <!--            <i class="align-middle" data-feather="search"></i>-->
    <!--        </button>-->
    <!--    </div>-->
    <!--</form>-->
<?php }  else { ?>

<a href='/system/op_dashboard'><img src='https://kprm.co.in/system/img/kprm.jpg' height='50px'></a>
<!--<a href='/system/op_dashboard'><span style='font-size:24px'><strong>Dashboard </strong> </span></a>-->
<?php } ?>
    <ul class="navbar-nav d-none d-lg-flex">
        <!--<li class="nav-item px-2 dropdown">-->
        <!--    <a class="nav-link dropdown-toggle" href="#" id="megaDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true"-->
        <!--        aria-expanded="false">-->
        <!--        Quick Lunch-->
        <!--    </a>-->
        <!--    <div class="dropdown-menu dropdown-menu-start dropdown-mega" aria-labelledby="megaDropdown">-->
        <!--        <div class="d-md-flex align-items-start justify-content-start">-->
        <!--            <div class="dropdown-mega-list">-->

                    <!-- <div class="dropdown-header">Short </div> -->
                        <?php 
                        // $qmenu = get_all('op_menu','*',array('quick_lunch'=>'YES'));
                        // foreach((array)$qmenu['data'] as $menu)
                        // {
                        //     $link = $base_url.$menu['link'];
                        //   echo "<a class='dropdown-item' href='$link'>". $menu['title'] ."</a>";

                        // }
                       ?>
                    
                    <!-- </div> -->
                  
                  
        <!--        </div>-->
        <!--    </div>-->
        <!--</li>-->
    </ul>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">
            <!--<li class="nav-item dropdown">-->
            <!--    <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">-->
            <!--        <div class="position-relative">-->
            <!--            <i class="align-middle" data-feather="bell"></i>-->
            <!--            <span class="indicator">1</span>-->
            <!--        </div>-->
            <!--    </a>-->
               
            <!--</li>-->
            <!--<li class="nav-item">-->
            <!--    <a class="nav-icon d-none d-lg-block" href="https://m365.cloud.microsoft/chat/?titleId=T_f8aef828-2452-1746-48e7-9e2784371083&source=embedded-builder" target="_blank" title='Access Copilot'>-->
            <!--        <div class="position-relative">-->
            <!--       <i class="fa fa-cookie align-middle" style="color:#25D366; font-size:20px;"></i>-->
            <!--        </div>-->
            <!--    </a>-->
            <!--</li>-->
<div id="idle-timer" 
     style="display:none; position:fixed; top:10px; left:45%;
     background:#222; color:#fff; padding:10px 15px; border-radius:6px;
     font-size:14px; z-index:9999;">
  Session expires in <span id="idle-count">120</span>s
</div>


            
            
            
            <li class="nav-item">
                <a class="nav-icon d-none d-lg-block" href="<?= $base_url.'whatsapp/chat'?>" target="_blank">
                    <div class="position-relative">
                   <i class="fab fa-whatsapp align-middle" style="color:#25D366; font-size:20px;"></i>
                    <!-- <span data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i  class="align-middle" data-feather="message-circle"></i></span> -->
                    </div>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-icon js-fullscreen d-none d-lg-block" href="#">
                    <div class="position-relative">
                        <i class="align-middle" data-feather="maximize"></i>
                    </div>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-icon pe-md-0 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                   <img src='<?= $user_photo ?>' width='40px' height='40px'>
                   
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="<?= $base_url?>system/op_user_add?link=<?= encode("id=".$_SESSION['user_id'])?>"><i class="align-middle me-1" data-feather="user"></i> Profile</a>
                   
                    <a class="dropdown-item" href="op_change_password"><i class="align-middle me-1" data-feather="settings"></i> Change Password</a>
                    <?php if($user_type=='MEMBER' or $user_type=='DEV'){ 
                    ?>
                    <a class="dropdown-item" href="<?=$base_url?>public/member_bank"><i class="align-middle me-1" data-feather="home"></i> Bank Details</a>
                   <?php } ?> 
                    <!--<a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="help-circle"></i> Help Center</a>-->
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="logout()">Log out</a>
                </div>
            </li>
        </ul>
    </div>
</nav>