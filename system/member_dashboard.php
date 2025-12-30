<?php require_once('all_header.php');

if(isset($_REQUEST['dev_mode']) and $_REQUEST['dev_mode'] !='')
{
    $_SESSION['dev_mode']=$_REQUEST['dev_mode'];
}
if($user_type=='MEMBER') { 
                    
        $member =get_data('op_user', $user_name, null,'user_name')['data'];
        $member_id =get_data('member', $user_name, 'id','member_code')['data'];
        $level =get_data('member', $member_id ,'level')['data'];
       
        $myinfo = my_info($member_id);
}

?>
<style>
    h5{
        font-weight:400;
    }
</style>
			<main class="content">
				<div class="container-fluid p-0">
                 <main class="content">
        <div class="container-fluid p-3">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card p-3 shadow-sm">
                        <div class="text-center">
                            <img src="../upload/no_photo.jpg" class="rounded-thumbnail" width="100" height="100" alt="User Avatar">
                            <h4 class="mt-2 text-primary"> <?= $member['full_name'] ?> </h4>
                            <a class="btn btn-sm btn-danger text-light"> <?= $member['user_name'] ?> </a>
                            <a href='../public/sponser_tree' class='btn btn-sm btn-success'><i class='fa fa-seedling'></i> Team </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-4">
                            <a class="card p-3 text-center shadow-sm" href='../public/my_team'>
                                <h5>Total Team</h5>
                                <h3 class="text-danger"> <?= $myinfo['total_count'] ?> </h3>
                            </a>
                             <a class="card p-3 text-center shadow-sm" href='../public/team_booking'>
                                <h5>Total Point</h5>
                                <h3 class="text-danger"> <?= $myinfo['total_point'] ?> </h3>
                                <span class='btn btn-sm border border-info'> <?= "Left: " .$myinfo['left_point'] ." | Right: ". $myinfo['right_point'] ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a class="card p-3 text-center shadow-sm" href="../public/team_business">
                                <h5>Total Business</h5>
                                <h3 class="text-success"> ₹<?= total_team_business($member_id);?> </h3>
                            </a>
                            <div class="card p-3 text-center shadow-sm">
                                <h5>Your Level </h5>
                                <h3 class="text-success"> <?= $level ?> </h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <a class="card p-3 text-center shadow-sm" href="../public/member_income">
                                <h5>Total Income</h5>
                                <h3 class="text-info"> ₹ <?= my_income($member_id) ?> </h3>
                            </a>
                            <a  class="card p-3 text-center shadow-sm" href='../public/check_reward.php'>
                                <h5>Rewards</h5>
                                <span class="badge bg-danger p-2 mb-3"> Click to Check </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
           
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <h4>Month Wise Business</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr class='bg-warning'>
                                    
                                    <th>Month Year</th>
                                    <td align='right'><b>Total</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php
                                    
                                     $business = monthly_business($member_id);
                                     $total = 0;
                                     
                                    foreach ((array)$business as $mc) {
                                        $total += $mc['total'];
                                        echo "<tr><td>". $mc['month_year']. "</td>";
                                        echo "<td align='right'>".$mc['total']."</td></tr>";
                                    }
                                    echo "<tr class='bg-dark text-light'><th>Grand Total</td>";
                                    echo "<td align='right'><b>$total</b></td></tr>";
                                    
                                    ?>
                            
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 shadow-sm">
                        <h4>Monthly Income Overview</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr class='bg-warning'>
                                    
                                    <th>Month Year</th>
                                    <td align='right'><b>Total</b></td>
                                    <td align='right'><b>Status</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php
                                    
                                     $business = get_all('member_txn','*',['member_id'=>$member_id], 'month_name desc')['data'];
                                     $ctotal = 0;
                                     
                                    foreach ((array)$business as $mc) {
                                        $ctotal += $mc['net_payable'];
                                        $month_name =$mc['month_name'];
                                        echo "<tr><td><a href='../public/member_comm.php?month_name=$month_name'>".$month_name . "</a></td>";
                                        echo "<td align='right'>{$mc['net_payable']}</td>";
                                        echo "<td align='right'>".show_status($mc['payment_status'])."</td></tr>";
                                    }
                                    echo "<tr class='bg-dark text-light'><th>Grand Total</td>";
                                    echo "<td align='right'><b>$ctotal</b></td>";
                                    echo "<td align='right'></td></tr>";
                                    ?>
                            
                            </tbody>
                        </table>
                    </div>
                </div>
   
            </div>
        </div>
    </main>
					
				

				</div>
			</main>
<?php require_once('footer.php'); ?>
</body>
</html>