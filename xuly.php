<?php	
	include('config.php');
	
	
	// Tim kiếm thửa đất
	if(isset($_GET['timkiem_thuadat'])){
		$shbando = $_GET['shbando'];
		$shthua = $_GET['shthua'];
		
		if($shbando=='' && $shthua==''){
			echo 'Mời bạn nhập số tờ, số thửa!!!';
		}else{
			if($shbando==''){
				$truyvan = "SELECT id,shbando,shthua,tenchusdd,diachi FROM tanbinh_dongxoai WHERE shthua = '".$shthua."' LIMIT 50";
			}elseif($shthua==''){
				$truyvan = "SELECT id,shbando,shthua,tenchusdd,diachi FROM tanbinh_dongxoai WHERE shbando = '".$shbando."' LIMIT 50";
			}else{
				$truyvan = "SELECT id,shbando,shthua,tenchusdd,diachi FROM tanbinh_dongxoai WHERE shbando = '".$shbando."' AND shthua = '".$shthua."' LIMIT 50";
			}
			
			$thucthi=pg_query($dbcon,$truyvan);
			
			while($kq=pg_fetch_assoc($thucthi)){
				/* echo $kq['shbando'].'|'.$kq['shthua'].'|';
				echo $kq['tenchusdd'].'<br>'; */				
				
				echo '<b>'.$kq['tenchusdd'].'</b><br>';
				echo 'Số tờ: '.$kq['shbando'].' | Số thửa: '.$kq['shthua'].' <br>';
				echo '<a href="#" onclick="highlight_zoom('.$kq['id'].', event);">Zoom</a><br>';
				echo '<hr style="margin: 3px;">';
			}
		}
	}
	
	//get geojson
	if(isset($_GET['get_geojson'])){
		$id = $_GET['id'];
		
		$truyvan = "SELECT ST_AsGeoJSON(geom) as geojson
					FROM tanbinh_dongxoai
					WHERE id = '".$id."'
					LIMIT 50";
		$thucthi=pg_query($dbcon,$truyvan);
		
		while($kq=pg_fetch_assoc($thucthi)){
			echo $kq['geojson'];
		}
	}
?>