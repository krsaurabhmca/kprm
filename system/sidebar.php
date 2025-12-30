<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="wrapper">
<?php if($user_type =='ADMIN' or $user_type =='DEV'){ ?>
		<nav id="sidebar" class="sidebar js-sidebar">
<?php }else{ ?>
		<nav id="sidebar" class="sidebar js-sidebar" style="display: none;">
<?php } ?>
			<div class="sidebar-content js-simplebar">
				<a class="sidebar-brand" href="<?= $base_url?>index">
					<span class="sidebar-brand-text align-middle">
						<?= @$inst_name; ?>
						<sup><small class="badge bg-warning text-uppercase"><?= @$tag ?> </small></sup>
					</span>
					<svg class="sidebar-brand-icon align-middle" width="32px" height="32px" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.5"
						stroke-linecap="square" stroke-linejoin="miter" color="#FFFFFF" style="margin-left: -3px">
						<path d="M12 4L20 8.00004L12 12L4 8.00004L12 4Z"></path>
						<path d="M20 12L12 16L4 12"></path>
						<path d="M20 16L12 20L4 16"></path>
					</svg>
				</a>

				<div class="sidebar-user">
					<div class="d-flex justify-content-center">
						<div class="flex-shrink-0 bg-light p-1">
						 <img src='<?= $user_photo ?>' width='40px' height='40px' class="avatar img-fluid rounded">
						</div>
						<div class="flex-grow-1 ps-2">
							<a class="sidebar-user-title dropdown-toggle" href="#" data-bs-toggle="dropdown">
								<?= @$_SESSION['user_name']; ?>
							</a>
							<div class="dropdown-menu dropdown-menu-start">
								<!-- <a class="dropdown-item" href="#pages-profile.html"><i class="align-middle me-1" data-feather="user"></i> Profile</a> -->
								<a class="dropdown-item" href="op_change_password"><i class="align-middle me-1" data-feather="settings"></i> Change Password</a>
								<a class="dropdown-item" href="#"><i class="align-middle me-1" data-feather="help-circle"></i> Help Center</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#" onclick='logout()'>Log out</a>
							</div>
							<br>
							<div id='userinfo' class="sidebar-user-subtitle badge text-warning" data-user_type='<?= @$_SESSION['user_type']; ?>' data-user_name='<?= @$_SESSION['user_name']; ?>' data-device-type=''> <?= @$_SESSION['user_type']; ?>  <span class='badge bg-warning'><?= @$DEV_MODE; ?> </span></div>
						</div>
					</div>
				</div>
                <li class="sidebar-item">
				<a href="<?= $base_url?>index" class="sidebar-link collapsed">
					<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
				</a>
				</li>
				<ul class="sidebar-nav">
					<li class="sidebar-header">
						Main Menu 
					</li>

					<?php $x = create_menu();
					
					print_r($x);
					?>

				
				<?php if(strtoupper($user_type) =='DEV') { ?>
            		<li class="sidebar-header">
						Developer Menu
					</li>
					<li class="sidebar-item">
						<a data-bs-target="#form-plugins" data-bs-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Developer Tools</span>
						</a>
						<ul id="form-plugins" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
							<li class="sidebar-item"><a class="sidebar-link" accesskey="c" href="<?= $base_url?>system/op_config_manage">Config Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="m" href="<?= $base_url?>system/op_table">Table Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="e" href="<?= $base_url?>system/op_table_manager">Table Editor</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="d" href="<?= $base_url?>system/op_data_manager">Data Manager</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_menu_manage">Menu Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_role_manager">Role Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="i" href="<?= $base_url?>system/op_import">Bulk Import</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="u" href="<?= $base_url?>system/op_user_manage"> Users Manger</a></li>
							<li class="sidebar-item"><a class="sidebar-link text-bold text-warning" accesskey="r" href="<?= $base_url?>system/op_recycle_bin">Recycle Bin</a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_backup" accesskey="b" >Create Backup </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_icons" accesskey="b" >All Icons </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_settings_manage" accesskey="b" >Settings Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_docs_manager" accesskey="b" >Document Manager </a></li>
						
						</ul>
					</li>
					
				<?php } ?>


				<?php if(strtoupper($user_type) =='ADMIN') { ?>
            		<li class="sidebar-header">
						Settings
					</li>
					<li class="sidebar-item">
						<a data-bs-target="#form-plugins" data-bs-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="check-square"></i> <span class="align-middle">Admin Tools</span>
						</a>
						<ul id="form-plugins" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
							<li class="sidebar-item"><a class="sidebar-link" accesskey="e" href="<?= $base_url?>system/op_table_manager">Table Editor</a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_menu_manage">Menu Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="n" href="<?= $base_url?>system/op_role_manager">Role Manager </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey="u" href="<?= $base_url?>system/op_user_manage">Users</a></li>
							<li class="sidebar-item"><a class="sidebar-link " href="<?= $base_url?>system/op_backup" accesskey="b" >Create Backup </a></li>
							<li class="sidebar-item"><a class="sidebar-link" accesskey ="s" href="<?= $base_url?>system/op_settings">Settings </a></li>
						
						</ul>
					</li>
				<?php } ?>
				</ul>
                <?php if($user_type=='ADMIN'){ ?>
				<div class="sidebar-cta">
					<div class="sidebar-cta-content">
						<strong class="d-inline-block mb-2"><?= $help_title ?></strong>
						<div class="mb-1 text-sm">
							<?= $help_msg ?>
						</div>

						<div class="d-grid">
							<a href="https://wa.me/+91<?=$dev_contact?>?msg=Message%20from%20<?= $inst_name?>" class="btn btn-outline-success" target="_blank"><i class='fab fa-whatsapp'></i> WhatsApp</a>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
		</nav>