<?php
require_once('op_lib.php');
require('fpdf/html2pdf.php');
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);
$pdf->SetTitle($inst_name);

$param = decode($_GET['link']);
$table_name = $param['table'];
$id = $param['id'];
$master = get_multi_data('op_master_table', array('table_name'=>$table_name,'is_edit'=>'YES','status'=>'ACTIVE'),' order by display_id ');
$data  = get_data($table_name, $id)['data'];

    //$pdf->Image($base_url.'/img/logo.png', 10, 10, 100,100);
    $pdf->Image($base_url.'system/img/logo.png', 15, 10, 30, 0, 'PNG');
    $pdf->SetFont('Arial','B',18);
	$pdf->Cell(180,10,strtoupper($full_name),0,1,'C');
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(180,6,$inst_address1. " ". $inst_address2,0,1,'C');
	$pdf->Cell(180,6,$base_url." | ".$inst_contact,0,1,'C');
	$pdf->Cell(180,2,"","T",1);
	
	$pdf->SetFont('Arial','',10);
	foreach ($master['data'] as $col) {
        $info ='';
		$key_value  = $data[$col['column_name']];
		$display_key  = ($col['display_name']=='')?add_space($col['column_name']):$col['display_name'];
		$display_type  = $col['input_type'];
		$extra  = $col['extra'];
		//$display_val = display_value($key_value, $display_type);
		$i=1;
		if($display_type=='List-Dynamic')
			{
				$input  = explode(',',$col['input_value']);
				$dval1 = get_data($input[0], $key_value, $input[1])['data'];
				$dval2 = '';
				if(isset($input[2]) and $input[2]!='')
				{
				$dval2 = " [". get_data($input[0],$key_value,$input[2])['data']. "]"; 
				}
				$display_val = $dval1 . $dval2;
			} else {
				//$display_val = display_value($key_value,$display_type,'pdf');
				$display_val = $key_value; 
			}
		if($display_type=='Label')
		{
			
			$pdf->Cell(180,10,strtoupper($display_key),1,1);

		} else {
			$info = $info . "<tr><td><b>" . $display_key . "</b></td><td>:</td><td>" . $display_val . "</td></tr>";
			
			if($display_val !='' and strlen($display_val)>70)
			{
		    	$pdf->Cell(50,10,add_space($display_key),0);
		    	$pdf->Cell(10,10," : ",0,0);
    			$pdf->MultiCell(100,10,$display_val,0,1);
			}
			else{
			$pdf->Cell(50,10,add_space($display_key),0,0);
			$pdf->Cell(10,10," : ",0,0);
			$pdf->Cell(100,10,$display_val,0,1);
			}
		}
		$i++;
	}
    $pdf->Cell(180,10,"*** End of Report ***","0",1,"C");
$pdf->Output($table_name.'_'.$master['data'][0]['column_name'].'.pdf', 'D');
//$pdf->Output();
?>