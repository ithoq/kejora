<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
	Pengaturcara: Md Ridzuan bin Mohammad Latiah
	create: 18-Ogos-2011
*/
class Cron extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		if(!$this->input->is_cli_request())
	     {
	       echo 'Not allowed';
	       exit();
	     }
	}

	public function index()
	{
		$this->load->model('mdepartment');
		$dept = $this->mdepartment->recur_dept(1);
		foreach($dept->result() as $row)
		{
			print_r($row->DEPTNAME);
		}
	}

	public function gen_final_attendance_date_by_user($user_id, $tkh)
	{
		//$this->load->model('muserinfo');
		$this->load->model('mwbb');
		$this->load->model('mlaporan');
		$this->load->model('mcuti', 'cuti');

		$tkh_semalam = $tkh;

		//$users = $this->muserinfo->getAllUser();

		echo '--------------------------------------------------' . "\n";
		echo 'Start Re-Generated by user on: ' . $user_id . " " . date('Y-m-d g:i:s a') . "\n";

		//foreach($users->result() as $user)
		//{
			//if($user->DEFAULTDEPTID != -1)
			//{
				$shift = $this->mwbb->get_start_shift_by_user_wbb($user_id, date('Y-m-d', strtotime($tkh_semalam)));
				$cuti = $this->cuti->get_by_bulan_tahun(date('m', strtotime($tkh_semalam)), date('Y', strtotime($tkh_semalam)));
				$this->mlaporan->gen_update_final_attendance($user_id, $tkh_semalam, $shift, $cuti);
			//}
		//}

		echo 'End Re-Generated by user on: ' . date('Y-m-d g:i:s a') . "\n";
		echo '--------------------------------------------------' . "\n";
	}

	public function gen_final_attendance_date($tkh)
	{
		echo '--------------------------------------------------' . "\n";
		echo 'Start Re-Generated on: ' . date('Y-m-d g:i:s a') . "\n";

		$this->load->model('muserinfo');
		$this->load->model('mwbb');
		$this->load->model('mlaporan');
		$this->load->model('mcuti', 'cuti');

		$tkh_semalam = $tkh;

		$users = $this->muserinfo->getAllUser();


		foreach($users->result() as $user)
		{
			if($user->DEFAULTDEPTID != -1)
			{
				$shift = $this->mwbb->get_start_shift_by_user_wbb($user->USERID, date('Y-m-d', strtotime($tkh_semalam)));
				$cuti = $this->cuti->get_by_bulan_tahun(date('m', strtotime($tkh_semalam)), date('Y', strtotime($tkh_semalam)));
				$this->mlaporan->gen_update_final_attendance($user->USERID, $tkh_semalam, $shift, $cuti);
			}
		}

		echo 'End Re-Generated on: ' . date('Y-m-d g:i:s a') . "\n";
		echo '--------------------------------------------------' . "\n";
	}

	public function re_gen_final_attendance()
	{
		$this->load->model('muserinfo');
		$this->load->model('mwbb');
		$this->load->model('mlaporan');
		$this->load->model('mcuti', 'cuti');

		$tkh_semasa = date('Y-m-d');
		$tkh_semalam = date('Y-m-d', strtotime('-1 day', strtotime($tkh_semasa)));

		$users = $this->muserinfo->getAllUser();

		echo '--------------------------------------------------' . "\n";
		echo 'Start Re-Generated on: ' . date('Y-m-d g:i:s a') . "\n";

		foreach($users->result() as $user)
		{
			if($user->DEFAULTDEPTID != -1)
			{
				$shift = $this->mwbb->get_start_shift_by_user_wbb($user->USERID, date('Y-m-d', strtotime($tkh_semalam)));
				$cuti = $this->cuti->get_by_bulan_tahun(date('m', strtotime($tkh_semalam)), date('Y', strtotime($tkh_semalam)));
				$this->mlaporan->gen_update_final_attendance($user->USERID, $tkh_semalam, $shift, $cuti);
			}
		}

		echo 'End Re-Generated on: ' . date('Y-m-d g:i:s a') . "\n";
		echo '--------------------------------------------------' . "\n";
	}

	/*
		Menghantar emel harian kehadiran
	*/
	public function laporan_harian_kehadiran($dept_id = 0)
	{
		$this->load->model('mcuti');
		$this->load->model('mdepartment');
		$this->load->model('mlaporan');

		if($this->mcuti->check_cuti(date('d'), date('m'), date('Y')) == 0)
		{
			if($dept_id == 0)
			{
				$departments = $this->mdepartment->get_sub_dept();
				foreach($departments->result() as $department)
				{
					$data['department'] = $department->DEPTNAME;
					$data['tarikh'] = date('Y-m-d');
					$data['bulan'] = $this->config->item('pcrs_bulan');
					$data['hari'] = $this->config->item('pcrs_hari');
					$data['kakitangan'] = $this->mlaporan->get_staff_kehadiran_harian($department->DEPTID, date('d'), date('m'), date('Y'));
					$email_title = '[PCRS] Laporan Kehadiran Harian ' . date('d/m/Y');
					$report = $this->load->view('laporan/v_emel_laporan_harian', $data, TRUE);
					$rcpt = $this->mlaporan->get_rcpt_laporan_harian($department->DEPTID);

					foreach($rcpt->result() as $row)
					{
						$this->load->library("notifikasi");
						$this->notifikasi->sendEmail($row->Email, $email_title, $report);
					}
				}
			}
			else
			{
				$data['department'] = $this->mdepartment->get_department_name($dept_id);
				$data['tarikh'] = date('Y-m-d');
				$data['bulan'] = $this->config->item('pcrs_bulan');
				$data['hari'] = $this->config->item('pcrs_hari');
				$data['kakitangan'] = $this->mlaporan->get_staff_kehadiran_harian($dept_id, date('d'), date('m'), date('Y'));
				$email_title = '[PCRS] Laporan Kehadiran Harian ' . date('d/m/Y');
				$report = $this->load->view('laporan/v_emel_laporan_harian', $data, TRUE);
				$rcpt = $this->mlaporan->get_rcpt_laporan_harian($dept_id);

				foreach($rcpt->result() as $row)
				{
						$this->load->library("notifikasi");
						$this->notifikasi->sendEmail($row->Email, $email_title, $report);
				}
			}
		}
	}

	public function laporan_penuh_harian_kehadiran()
	{
		$this->load->model('mcuti');
		$this->load->model('mdepartment');
		$this->load->model('mlaporan');

		$tkh_semalam = strtotime('-1 day', strtotime(date('Y-m-d')));

		if($this->mcuti->check_cuti(date('d', $tkh_semalam), date('m', $tkh_semalam), date('Y', $tkh_semalam)) == 0)
		{
			$this->load->library("notifikasi");
			$departments = $this->mdepartment->get_user_dept();
			foreach($departments->result() as $department)
			{
				$data['department'] = $department->DEPTNAME;
				$data['tarikh'] = date('Y-m-d', $tkh_semalam);
				$data['bulan'] = $this->config->item('pcrs_bulan');
				$data['hari'] = $this->config->item('pcrs_hari');
				$data['kakitangan'] = $this->mlaporan->get_staff_kehadiran_harian($department->DEPTID, date('d', $tkh_semalam), date('m', $tkh_semalam), date('Y', $tkh_semalam));
				$email_title = '[eMASA] Laporan Penuh Kehadiran Harian ' . date('d/m/Y', $tkh_semalam);
				$report = $this->load->view('laporan/v_emel_laporan_penuh_harian', $data, TRUE);
				$rcpt = $this->mlaporan->get_rcpt_laporan_harian($department->DEPTID);

				foreach($rcpt->result() as $row)
				{
					$this->notifikasi->sendEmail($row->Email, $email_title, $report);
				}
			}
		}
	}

	public function re_send_laporan_harian_kehadiran($dept_id, $tarikh)
	{
		$this->load->model('mcuti');
		$this->load->model('mdepartment');
		$this->load->model('mlaporan');

		if($this->mcuti->check_cuti(date('d', strtotime($tarikh)), date('m', strtotime($tarikh)), date('Y', strtotime($tarikh))) == 0)
		{
			$data['department'] = $this->mdepartment->get_department_name($dept_id);
			$data['tarikh'] = date('Y-m-d', strtotime($tarikh));
			$data['bulan'] = $this->config->item('pcrs_bulan');
			$data['hari'] = $this->config->item('pcrs_hari');
			$data['kakitangan'] = $this->mlaporan->get_staff_kehadiran_harian($dept_id, date('d'), date('m'), date('Y'));
			$email_title = '[eMASA] Laporan Kehadiran Harian ' . date('d/m/Y');
			$report = $this->load->view('laporan/v_emel_laporan_harian', $data, TRUE);
			$rcpt = $this->mlaporan->get_rcpt_laporan_harian($dept_id);
			foreach($rcpt->result() as $row)
			{
				$this->load->library("notifikasi");
				$this->notifikasi->sendEmail($row->Email, $email_title, $report);
			}
		}
	}

	public function re_laporan_penuh_harian_kehadiran($tarikh)
	{
		$this->load->model('mcuti');
		$this->load->model('mdepartment');
		$this->load->model('mlaporan');

		$tkh_semalam = strtotime($tarikh);

		if($this->mcuti->check_cuti(date('d', $tkh_semalam), date('m', $tkh_semalam), date('Y', $tkh_semalam)) == 0)
		{
			$departments = $this->mdepartment->get_user_dept();
			foreach($departments->result() as $department)
			{
				$data['department'] = $department->DEPTNAME;
				$data['tarikh'] = date('Y-m-d', $tkh_semalam);
				$data['bulan'] = $this->config->item('pcrs_bulan');
				$data['hari'] = $this->config->item('pcrs_hari');
				$data['kakitangan'] = $this->mlaporan->get_staff_kehadiran_harian($department->DEPTID, date('d', $tkh_semalam), date('m', $tkh_semalam), date('Y', $tkh_semalam));
				$email_title = '[eMASA] Laporan Penuh Kehadiran Harian ' . date('d/m/Y', $tkh_semalam);
				$report = $this->load->view('laporan/v_emel_laporan_penuh_harian', $data, TRUE);
				$rcpt = $this->mlaporan->get_rcpt_laporan_harian($department->DEPTID);
				foreach($rcpt->result() as $row)
				{
					$this->load->library("notifikasi");
					$this->notifikasi->sendEmail($row->Email, $email_title, $report);				}
			}
		}
	}

	Public function lapor_lewat_hadir()
	{
		echo "Hantar sms untuk lewat hadir trigger \n";

		$this->load->model('mlaporan');

		$tkh_semasa = date('Y-m-d');
		$rst_lewat = $this->mlaporan->get_lewat($tkh_semasa);

		if($rst_lewat->num_rows())
		{
			foreach($rst_lewat->result() as $lewat)
			{
				$this->maklumatLewat($lewat);
			}
		}
	}

	/* Modified by : Md Ridzuan */
	/* Tarikh : 10.11.2014 */
	/* Masa : 17:22 */
	/* Catatan : Untuk hantar sms kepada SUK, hanya user yang declare sebagai ketua jabatan dalam sistem sahaja. */

	/* Modified by : Md Ridzuan */
	/* Tarikh : 20.11.2014 */
	/* Masa : 15:00 */
	/* Catatan : Untuk hantar email */
	//private function _sms_kelewatan($id, $user_id, $punch_in)
	private function maklumatLewat($objLewat)
	{
		$this->load->model('mlaporan');
		$this->load->model('mwbb');
		$this->load->model('muserlewat');
		$this->load->model('mpelulus');

		$rst_staff = $this->mlaporan->get_staff_late($objLewat->USERID, date('m',strtotime($objLewat->CHECKTIME)), date('Y',strtotime($objLewat->CHECKTIME)));

		if($rst_staff->num_rows)
		{
			$this->load->library("notifikasi");

			$row_staff = $rst_staff->row();
			$lewat = pcrs_seconds_to_hms(strtotime($objLewat->CHECKTIME) - strtotime('+60 second', strtotime(date('Y-m-d', strtotime($objLewat->CHECKTIME)) . ' ' . $this->mwbb->get_start_shift_by_user($objLewat->USERID, date('m',strtotime($objLewat->CHECKTIME)), date('Y',strtotime($objLewat->CHECKTIME))))));
			$bil_lewat = $row_staff->LATE_COUNT;
			$bulan = $this->config->item('pcrs_bulan');
			$message = $row_staff->Name . ", " . $row_staff->SSN . " lewat " . $lewat . " saat pada " . date('d/m/Y', strtotime($objLewat->CHECKTIME)) . ". Telah lewat " . $bil_lewat . " kali pada bulan " . $bulan[date('m',strtotime($objLewat->CHECKTIME))];
			$subject = "[PCRS] " . $row_staff->Name . " Telah Hadir Lewat ke Pejabat pada " . date('d/m/Y', strtotime($objLewat->CHECKTIME));

			if($row_staff->SS_SATU == 'SS')
			{
				if($this->mpelulus->chk_kj($row_staff->USERID))
				{
					$this->notifikasi->sendSms($row_staff->TEL_PEG_SATU, $message);
					$this->notifikasi->sendEmail($row_staff->MEL_PEG_SATU, $subject, $message,'mdridzuan@melaka.gov.my');
				}
			}
			else
			{
				$this->notifikasi->sendSms($row_staff->TEL_PEG_SATU, $message);
				$this->notifikasi->sendEmail($row_staff->MEL_PEG_SATU, $subject, $message,'mdridzuan@melaka.gov.my');
			}

			if($bil_lewat >= 3)
			{
				if($row_staff->SS_SATU == 'SS')
				{
					if($this->mpelulus->chk_kj($row_staff->USERID))
					{
						$this->notifikasi->sendSms($row_staff->TEL_PEG_DUA, $message);
						$this->notifikasi->sendEmail($row_staff->MEL_PEG_DUA, $subject, $message,'mdridzuan@melaka.gov.my');
					}
				}
				else
				{
					$this->notifikasi->sendSms($row_staff->TEL_PEG_DUA, $message);
					$this->notifikasi->sendEmail($row_staff->MEL_PEG_DUA, $subject, $message,'mdridzuan@melaka.gov.my');				}
			}
			echo "Update table" . $objLewat->ID . "\n";
			echo $objLewat->USERID . " send\n";
			$this->mlaporan->do_update_lewat($objLewat->ID);
		}
	}

	public function gen_user_id()
	{
		$this->load->model('muserinfo');
		$this->load->model('mkodwarna');

		$kakitangan = $this->muserinfo->getAllUser();

		foreach($kakitangan->result() as $staff)
		{
			$field['kw_userid'] = $staff->USERID;
			$field['kw_kod'] = 1;
			$this->mkodwarna->do_save($field);
		}
	}

	public function test_sms($telefon)
	{
		$this->load->library("notifikasi");
		$message = "Syed Munawir, 123456789012 lewat 00:02:44 saat pada 29/06/2014. Telah lewat 4 kali pada bulan Jun";
		echo $this->notifikasi->sendSms($telefon, $message);
	}

	public function test_email()
	{
		$this->load->library("notifikasi");
		$subject = "Punctuality Cascading Reporting System";
		$message = "Syed Munawir, 123456789012 lewat 00:02:44 saat pada 29/06/2014. Telah lewat 4 kali pada bulan Jun";
		$this->notifikasi->sendEmail('mdridzuan@melaka.gov.my', $subject, $message, 'demo@melaka.gov.my');
	}

	public function re_gen()
	{
		$tkh_mula = '2014-09-01';
		$tkh_akhir = '2014-10-01';
		$tkh = $tkh_mula;
		do{
			echo($tkh . "\n");
			$this->gen_final_attendance_date($tkh);
			$tkh = date('Y-m-d', strtotime('+1 day', strtotime($tkh)));
		} while ($tkh != $tkh_akhir);
	}

	public function re_gen_date_range($tkh1, $tkh2)
	{
		$tkh_mula = $tkh1;
		$tkh_akhir = $tkh2;
		$tkh = $tkh_mula;
		do{
			echo($tkh . "\n");
			$this->gen_final_attendance_date($tkh);
			$tkh = date('Y-m-d', strtotime('+1 day', strtotime($tkh)));
		} while (strtotime($tkh) <= strtotime($tkh_akhir));
	}

	public function gen_surat_ts()
	{
		$this->load->model('mlaporan');
		$this->load->model('mwbb');

		$html = "";
		$bulan = date('m', strtotime('-1 day'));
		$tahun = date('Y', strtotime('-1 day'));
		$staff_info = array();
		$staff_counter = 0;

		$layak_ts = $this->mlaporan->get_kakitangan_layak_ts($bulan, $tahun);

		if($layak_ts->num_rows() != 0)
		{
			foreach($layak_ts->result() as $layak)
			{
				$data['nama'] = $layak->Name;
				$data['no_kp'] = $layak->SSN;
				$data['jawatan'] = $layak->TITLE;
				$data['bahagian'] = $layak->DEPTNAME;
				$data['pegawai'] = $layak->pegawai;
				$data['wbb'] = $this->mwbb->get_staff_wbb($layak->rpt_userid, $bulan, $tahun);
				$data['ts_rekod'] = $this->mlaporan->get_kakitangan_layak_ts_info($layak->rpt_userid, $bulan, $tahun);
				$html .= $this->load->view('laporan/v_tpl_surat_tunjuk_sebab', $data, TRUE);
				$staff_counter++;
			}
			//echo $html;
			//exit();
			$pdf_param = array('orientation'=>'P', 'marges'=>array(20,10,20,10), 'langue'=>'en');
			pcrs_render_pdf_download($pdf_param, $html);
		}

	}

	public function gen_surat_ts_bulanan($b, $t)
	{
		$this->load->model('mlaporan');
		$this->load->model('mwbb');
		$this->load->model('mdepartment');

		$html = "";
		$bulan = $b;
		$tahun = $t;
		$staff_info = array();
		$staff_counter = 0;

		$departs = $this->mdepartment->get_sub_dept();
		//print_r($departs);
		//die();
		foreach($departs->result() as $depart)
		{
			$html = "";
			$layak_ts = $this->mlaporan->get_kakitangan_layak_ts($bulan, $tahun, $depart->DEPTID);
			if($layak_ts->num_rows() != 0)
			{
				foreach($layak_ts->result() as $layak)
				{
					$data['nama'] = $layak->Name;
					$data['no_kp'] = $layak->SSN;
					$data['jawatan'] = $layak->TITLE;
					$data['bahagian'] = $layak->DEPTNAME;
					$data['pegawai'] = $layak->pegawai;
					$data['wbb'] = $this->mwbb->get_staff_wbb($layak->rpt_userid, $bulan, $tahun);
					$data['ts_rekod'] = $this->mlaporan->get_kakitangan_layak_ts_info($layak->rpt_userid, $bulan, $tahun);
					$html .= $this->load->view('laporan/v_tpl_surat_tunjuk_sebab', $data, TRUE);
					$staff_counter++;
				}
				//echo $html;
				//exit();
				$pdf_param = array('orientation'=>'P', 'marges'=>array(20,10,20,10), 'langue'=>'en');
				//echo $html;
				//pcrs_render_pdf_download($pdf_param, $html);
				pcrs_send_email('mdridzuan@melaka.gov.my', 'Ujian', 'test', array(), array(), pcrs_render_pdf_none_download($pdf_param, $html));
				die();
			}
		}
	}

	public function jana_surat_tunjuk_sebab_lewat($b, $t)
	{
		$this->load->model('mlaporan');
		$this->load->model('mwbb');
		$this->load->model('mdepartment');
		$this->load->model('mpelulus');
		$this->load->model('mrole');

		$tmp_path = $this->config->item('pcrs_tmp_file');
		$filename = 'surat_tunjuk_sebab.pdf';
		$html = "";
		$bulan = $b;
		$tahun = $t;
		$staff_info = array();
		$staff_counter = 0;

		$layak_ts = $this->mlaporan->get_staff_lewat_ts($bulan, $tahun);
		foreach($layak_ts->result() as $layak)
		{
			$html = "";
			$data['nama'] = $layak->Name;
			$data['no_kp'] = $layak->SSN;
			$data['jawatan'] = $layak->TITLE;
			$data['bahagian'] = $layak->DEPTNAME;
			$data['dept_id'] = $layak->DEFAULTDEPTID;
			$data['pegawai'] = $this->mpelulus->get_pelulus_kj($layak->DEFAULTDEPTID);
			$data['wbb'] = $this->mwbb->get_staff_wbb($layak->USERID, $bulan, $tahun);
			$data['ts_rekod'] = $this->mlaporan-> get_kakitangan_layak_lewat_ts_info($layak->USERID, $bulan, $tahun);
			$html .= $this->load->view('laporan/v_tpl_surat_tunjuk_sebab_lewat', $data, TRUE);
			$pdf_param = array('orientation'=>'P', 'marges'=>array(20,10,20,10), 'langue'=>'en');
			$tajuk = "[PCRS] " . $layak->Name . " Surat Tunjuk Sebab Pelanggaran Kad Perakam Waktu Tanpa Sebab dan Alasan Munasabah";
			$rcpt = $this->mrole->get_email_by_role($layak->DEFAULTDEPTID, 5);
			$rcpt[] = 'mdridzuan@melaka.gov.my';
			//echo $html;
			pcrs_render_pdf_tmp($pdf_param, $html, $filename);
			pcrs_send_email($layak->email, $tajuk, $html, array($layak->MEL_PEG_SATU,$layak->MEL_PEG_DUA), $rcpt, $tmp_path . '/' .  $filename);
			//pcrs_send_email($layak->email, $tajuk, $html, array('mdridzuan@melaka.gov.my'), array(), $tmp_path . '/' .  $filename);
			unlink($tmp_path . '/' .  $filename);
			//if($staff_counter == 3)
			//	die();

			$staff_counter++;
		}
		//echo $html;
		//die();

	}

	public function test_recursive()
	{
		$this->load->model('mdepartment');
		$rst = $this->mdepartment->recursive();
		$arrs = array();
		foreach($rst->result_array() as $row)
		{
			$arrs[] = $row;
		}
		//print_r($arrs);
		//$it = new RecursiveIteratorIterator(new RecursiveArrayIterator(pcrs_get_recursive_department($arrs,14)));
		//$test = pcrs_get_recursive_department($arrs);
		//print_r($test);
		print_r(pcrs_flatten(pcrs_get_recursive_department($arrs)));
		//print_r($it);
	}

	public function form()
	{
		$this->load->view('cron/form');
	}
	public function tree2_getdata()
	{
		$this->load->model('mdepartment');
		$id = $this->input->post('id') ? intval($this->input->post('id')) : 0;
		$result = array();
		$rst = $this->mdepartment->getUnitsPPP($id);
		foreach($rst->result() as $row)
		{
			$node = array();
			$node['id'] = $row->DEPTID;
			$node['text'] = $row->DEPTNAME;
			$node['state'] = $this->has_child($row->DEPTID) ? 'closed' : 'open';
			array_push($result,$node);
		}
		echo json_encode($result);
	}

	public function has_child($id)
	{
		$this->load->model('mdepartment');
    	$rs = $this->mdepartment->getUnitsPPP($id);
    	//$row = $rs->result_array();
    	return $rs->num_rows() > 0 ? true : false;
	}

	public function xtra()
	{
		$this->load->model('muserinfo');
		$rst = $this->muserinfo->getAllUser();

		foreach($rst->result() as $row)
		{
			$field = array('kw_userid'=>$row->USERID);
			$this->muserinfo->insert_xtra($field);
		}

	}

	public function cuti_hrmis_file($tarikh = false) // format yyyy-mm-dd
	{
		//$tkh_semasa = date('Y-m-d');
		//$output = "hrmisdata_10092015174714.zip";
		echo "---------------------------------------\n";
		echo "HRMIS GEN CUTI: " . date('Y-m-d h:i:s') . "\n";
		echo "---------------------------------------\n";

		$this->load->model('mhrmis');

		if($tarikh)
		{
			$tkh_semasa = $tarikh;
		}
		else
		{
			$tkh_semasa = date('Y-m-d');
		}

		$x = 0;
		while( $x<2 )
		{
			if($x==1)
			{
				echo "Fetch data for : " . date('Y-m-d',strtotime('-1 day',strtotime($tkh_semasa))) . "\n";
				$rst = $this->mhrmis->get_cuti_file($this->config->item('pcrs_hrmis_bu'), date('Y-m-d',strtotime('-1 day',strtotime($tkh_semasa)))); //date('Y-m-d',strtotime('-1 day',strtotime($tkh_semasa)))
			}
			else
			{
				echo "Fetch data for : " . $tkh_semasa . "\n";
				$rst = $this->mhrmis->get_cuti_file($this->config->item('pcrs_hrmis_bu'), $tkh_semasa); //date('Y-m-d',strtotime('-1 day',strtotime($tkh_semasa)))
			}

			$file_info = $rst['soap:Envelope']['soap:Body']['GetDataLeaveFileByDateResponse']['getFileResponseStreaming'];
			//print_r($file_info);

			$contents = $file_info['fileData'];
			$output = $file_info['fileName'];
			$bin = base64_decode($contents);
			file_put_contents($this->config->item('pcrs_tmp_file') . $output, $bin);

			//create a ZipArchive instance
			$zip = new ZipArchive;
			//open the archive
			if ($zip->open($this->config->item('pcrs_tmp_file') . $output) === TRUE)
			{
				//extract contents to /data/ folder
				$zip->extractTo($this->config->item('pcrs_tmp_file'));
				//close the archive
				$zip->close();

				$content_array = array();
				$zip = zip_open($this->config->item('pcrs_tmp_file') . $output);

				if ($zip)
				{
					while ($zip_entry = zip_read($zip))
					{
						$contents = file_get_contents($this->config->item('pcrs_tmp_file') . zip_entry_name($zip_entry));
						unlink($this->config->item('pcrs_tmp_file') . zip_entry_name($zip_entry));

						$newline = "\n";
						$splitcontents = explode($newline, $contents);
						$counter = 0;
						foreach ( $splitcontents as $color )
						{
							if($color)
							{
								$content_array[$counter] = array();
								$delimiter = "\t";
								$splitcontents1 = explode($delimiter, $color);

								foreach ( $splitcontents1 as $value )
								{
									$content_array[$counter][]= $value;
								}
								$counter = $counter+1;
							}
						}

						// jalan operasi
						$this->load->model("muserinfo");
						$this->load->model('mjustifikasi');

						foreach($content_array as $rcd)
						{

							$fields['hr_userid'] = $this->muserinfo->get_user_id_by_nokp($rcd[0]);
							$fields['hr_tkh_mula'] = date("Y-m-d", strtotime($rcd[4]));
							$fields['hr_tkh_tamat'] = date("Y-m-d", strtotime($rcd[5]));
							$fields['hr_tkh_lulus'] = date("Y-m-d h:i:s", strtotime($rcd[6]));
							$fields['hr_alasan'] = $rcd[2];

							if($fields['hr_userid'] != 0)
							{
								$data_cuti = $this->mhrmis->chk_cuti($fields['hr_userid'], $tkh_semasa);
								if($data_cuti->num_rows()==0)
								{
									//Simpan dalam justifikasi
									if($fields['hr_tkh_mula']==$fields['hr_tkh_tamat'])
									{
										$field['justifikasi_rpt_id'] = 0;
										$field['justifikasi_alasan'] = "HRMIS : " . $fields['hr_alasan'];
										$field['justifikasi_tkh_terlibat'] = $fields['hr_tkh_mula'];
										$field['justifikasi_user_id'] = $fields['hr_userid'];
										$field['justifikasi_status'] = "L";
										$field['justifikasi_verifikasi'] = "HRMIS API";
										$field['justifikasi_tkh_verifikasi'] = $fields['hr_tkh_lulus'];
										if(!$this->mjustifikasi->chk_exists($field))
										{
											$this->mjustifikasi->simpan($field);
										}
										else
										{
											$this->mjustifikasi->do_update_hrmis($field);
										}
									}
									else
									{
										$tkh_mula = $fields['hr_tkh_mula'];
										$tkh_akhir = $fields['hr_tkh_tamat'];
										$tkh = $tkh_mula;

										do{
											$field['justifikasi_rpt_id'] = 0;
											$field['justifikasi_alasan'] = "HRMIS : " . $fields['hr_alasan'];
											$field['justifikasi_tkh_terlibat'] = $tkh;
											$field['justifikasi_user_id'] = $fields['hr_userid'];
											$field['justifikasi_status'] = "L";
											$field['justifikasi_verifikasi'] = "HRMIS API";
											$field['justifikasi_tkh_verifikasi'] = $fields['hr_tkh_lulus'];
											if(!$this->mjustifikasi->chk_exists($field))
											{
												$this->mjustifikasi->simpan($field);
											}
											else
											{
												$this->mjustifikasi->do_update_hrmis($field);
											}

											$tkh = date('Y-m-d', strtotime('+1 day', strtotime($tkh)));
										} while (strtotime($tkh) <= strtotime($tkh_akhir));
									}

									if($this->mhrmis->hrmis_cuti($fields))
									{
										echo $fields['hr_userid'] . " " . $rcd[0] . " Inserted\n";
									}
									else
									{
										echo "Fail\n";
									}
								}
								else
								{
									echo $fields['hr_userid'] . " " . $rcd[0] . " Skip\n";
								}
							}
							else
							{
								echo $fields['hr_userid'] . " " . $rcd[0] . " no record\n";
							}
						}
					}
				}
				else
				{
					echo "Fail to open zip file\n";
				}
				zip_close($zip);
				unlink($this->config->item('pcrs_tmp_file') . $output);
			}
			else
			{
				echo 'Failed to open the archive!';
			}

			echo "---------------------------------------\n";
			echo "END HRMIS GEN CUTI: " . date('Y-m-d h:i:s') . "\n";
			echo "---------------------------------------\n";
			$x++;
		}
	}

	public function get_data_from_device()
	{
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
		echo "---------------------------------------\n";
		echo "Fetch Data From Reader: " . date('Y-m-d h:i:s') . "\n";
		echo "---------------------------------------\n";

		$this->load->library('mytad',null,'mytad');
		$this->load->model('mmachine', 'machine');
		$this->load->model('mcheckinout', 'checkinout');
		$this->load->model('muserinfo', 'userinfo');
		$reader = $this->machine->get_enable();

		if(	$reader->num_rows())
		{

			foreach($reader->result() as $row)
			{
				echo "\tIP : " . $row->IP . "\n";
				echo "\t---------------------------------------\n";
				$att_logs = $this->mytad->get_attendance_log($row->IP, false, date('Y-m-d'));
				if(is_array($att_logs))
				{
					$sensorid = $row->ID;
					$sn = $row->sn;

					foreach($att_logs["Row"] as $rec)
					{
						if(pcrs_validate_date($rec["DateTime"]))
						{
							$data = array('USERID'=>$this->userinfo->getUserID($rec['PIN']),
								"CHECKTIME"=>$rec["DateTime"],
								"VERIFYCODE"=>$rec["Verified"],
								"SENSORID"=>$sensorid,
								"sn"=>$sn
							);

							if( !$this->checkinout->check_exists($data) )
							{
								//echo $this->checkinout->check_exists($data);
								//die();
								$this->checkinout->simpan($data);
								echo "\t Simpan \n";
							}
						}
						else
						{
							echo "\tDate not valid \n";
						}
					}
				}
				else
				{
					echo "\t " . $att_logs;
				}
				echo "\t End Fetch IP : " . $row->IP . "\n";
			}
		}
		else
		{
			echo "No Machine Enabled";
		}

		echo "---------------------------------------\n";
		echo "End Fetch Data From Reader: " . date('Y-m-d h:i:s') . "\n";
		echo "---------------------------------------\n";
	}

	public function jana_kod_warna()
	{
		if( strtotime("+1 day", strtotime(date('Y-m-') . pcrs_get_param('P_JUSTIFIKASI'))) == strtotime(date('Y-m-d')) )
		{
			$this->load->model('muserinfo', 'userinfo');
			$this->load->model('muserlewat', 'userlewat');
			$this->load->model('mkodwarna', 'kodwarna');

			$prev = strtotime('last month');
			$staffs = $this->userinfo->get_all_staff();

			foreach($staffs->result() as $staff)
			{
				$staff_lewat = $this->userlewat->get_user_lewat($staff->USERID, date('m', $prev), date('Y', $prev)); // no of bil. lewat
				$rst_warna = $this->userlewat->get_kod_warna($staff->USERID, date('m', $prev), date('Y', $prev)); // get prev month color code
				$field = array(
					"userid" => $staff->USERID,
					"bulan" => date('m'),
					"tahun" => date('Y')
				);

				if($rst_warna->num_rows())
				{
					$rec_warna = $rst_warna->row();
					$warna = 	$rec_warna->kod_warna;
				}
				else
				{
					$warna = 1;
				}

				if($staff_lewat->num_rows())
				{
					$lewat = $staff_lewat->row();
					switch($warna)
					{
						case 1:
							if($lewat->lewat >= 3)
								$warna++;
							break;

						case 2:
							if($lewat->lewat >=2)
								$warna++;
							break;
					}

					$field['kod_warna'] = $warna;
					$this->kodwarna->do_insert_sejarah_warna($field);
				}
				else
				{
					if( $warna > 1 )
						$warna--;

					$field['kod_warna'] = $warna;
					$this->kodwarna->do_insert_sejarah_warna($field);
				}
			}
		}
	}

	public function test_mailer()
	{
		$this->load->library("mailer");

		//Create a new PHPMailer instance
		$mail = $this->mailer;

		//dd($mail);
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 2;
		//Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host = "mail.kejora.gov.my";
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = 587;
		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;
		//Username to use for SMTP authentication
		$mail->Username = "pcrs";
		//Password to use for SMTP authentication
		$mail->Password = "Malay\$ia";
		//Set who the message is to be sent from
		$mail->setFrom('pcrs@kejora.gov.my', 'PCRS');
		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		$mail->addAddress('mdridzuan@melaka.gov.my', 'Md Ridzuan');
		//Set the subject line
		$mail->Subject = 'PHPMailer SMTP without auth test';
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML("<p>HelloWorld</p>"
		);
		//Replace the plain text body with one created manually
		$mail->AltBody = 'This is a plain-text message body';
		//Attach an image file
		//$mail->addAttachment('images/phpmailer_mini.png');

		//send the message, check for errors
		if (!$mail->send()) {
			echo "Mailer Error: " . $mail->ErrorInfo;
		} else {
			echo "Message sent!";
		}
	}
}
