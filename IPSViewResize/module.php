<?
require_once(__DIR__ . DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."IPSViewBase.php"); 

class IPSViewResize extends IPSViewBase
{
	
	// -------------------------------------------------------------------------
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyInteger("MasterView", 0);
		$this->RegisterPropertyInteger("ChildView", 0);

		$this->RegisterPropertyFloat("RatioX", 1.0);
		$this->RegisterPropertyFloat("RatioY", 1.0);

		$this->RegisterPropertyBoolean("AutoSync", true);		
		$this->RegisterTimer("CheckMasterTimer", 0, 'IPSView_CheckMaster($_IPS[\'TARGET\']);');
		$this->RegisterPropertyInteger("Interval", 30);
	}

	// -------------------------------------------------------------------------
	public function RequestAction($Ident, $Value)
	{
		switch($Ident) {
			default:
				throw new Exception("Invalid ident");
		}
	}
	
	// -------------------------------------------------------------------------
	public function ApplyChanges()
	{
		parent::ApplyChanges();
		
		$masterViewID     = $this->ReadPropertyInteger('MasterView');
		$childViewID      = $this->ReadPropertyInteger('ChildView');
		$autoSync         = $this->ReadPropertyBoolean('AutoSync');
		$ratioX           = $this->ReadPropertyFloat('RatioX');
		$ratioY           = $this->ReadPropertyFloat('RatioY');
		
		if (!IPS_MediaExists($masterViewID)) {
			$this->SetStatus(201); //No MasterView
		} else if (!IPS_MediaExists($childViewID)) {
			$this->SetStatus(202); //No TargetView
		} else if ($ratioX <= 0 || $ratioY <= 0) {
			$this->SetStatus(203); //Invalid Ratio
		} else if (!$autoSync) {
			$this->SetStatus(104); //Instanz ist inaktiv
		} else {
			$this->SetStatus(102); //Instanz ist aktiv
		}
		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];
		if ($status==102 && $autoSync) {
			$this->SetTimerInterval("CheckMasterTimer", $this->ReadPropertyInteger("Interval")*1000);
		} else {
			$this->SetTimerInterval("CheckMasterTimer", 0);
		}
	}
	
	// -------------------------------------------------------------------------
	public function Resize()
	{
		$this->SendDebug("Resize", "=====================================================================", 0);
		$this->SendDebug("Resize", "Execute Resize of  MasterView:", 0);
		$this->SendDebug("Resize", "Available Memory: ".ini_get('memory_limit'), 0);
		
		if ($this->IsInstancePropertiesValid()) {
			$masterViewID     = $this->ReadPropertyInteger('MasterView');
			$childViewID      = $this->ReadPropertyInteger('ChildView');
			$childViewFactorX = $this->ReadPropertyFloat('RatioX');
			$childViewFactorY = $this->ReadPropertyFloat('RatioY');
			
			$this->ShowMemoryUsage('Startup: ');

			$viewProperties = $this->GetViewProperties ($childViewID);
			$masterObj      = $this->GetViewObject($masterViewID);

			// Build Result Object
			$resultObj                   = $this->ResizeView ($masterObj, $childViewFactorX, $childViewFactorY);
			foreach ($viewProperties as $propertyName=>$propertyValue) {
				$resultObj[$propertyName]    = $propertyValue;
			}

			$this->ShowMemoryUsage('Build Result:');
			$masterObj = null;
		   
			// Write Data to View
			$this->WriteViewContent ($childViewID, $resultObj);
			$resultObj = null;
			$this->ShowMemoryUsage('finished:');

			$this->SendDebug("Resize", "=============================================================", 0);
			$this->SendDebug("Resize", "Successfully finished synchronization", 0);
			$this->SendDebug("Resize", "=============================================================", 0);
		}
	}

	// -------------------------------------------------------------------------
	public function CheckMaster()
	{
		$this->SendDebug("CheckMaster", "Check MasterView ...", 0);
		
		if ($this->IsInstancePropertiesValid()) {
			$masterViewID      = $this->ReadPropertyInteger('MasterView');
			$masterMedia       = IPS_GetMedia($masterViewID);
			$masterTimestamp   = $masterMedia['MediaUpdated'];
			$childViewID       = $this->ReadPropertyInteger('ChildView');
			$childMedia        = IPS_GetMedia($childViewID);
			$childTimestamp    = $childMedia['MediaUpdated'] ;

			if ($masterTimestamp >  $childTimestamp /*Masterview has changed*/) {
				$this->SendDebug("CheckMaster", "MasterView has changed -> trigger Resize of ChildView", 0);
				$this->Resize();
			}
			
			$this->SetTimerInterval("CheckMasterTimer", $this->ReadPropertyInteger("Interval")*1000);
		} else {
			$this->SetTimerInterval("CheckMasterTimer", 0);
			$this->SetStatus(104); //Instanz ist inaktiv
		}		
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------
	
	// -------------------------------------------------------------------------
	private function IsInstancePropertiesValid()
	{		
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];

		return ($status == 102 || $status == 104);
	}
	
	// -------------------------------------------------------------------------
	private function WriteViewContent ($ID, $viewObj) {
		$name    = IPS_GetName($ID);
		// Write Data to View

		$data        = json_encode($viewObj);
		$this->ShowMemoryUsage("json_encode '$name'");
		$viewObj = null;

		$content     = base64_encode($data);
		$this->ShowMemoryUsage("base64_encode '$name'");
		$data = null;

		IPS_SetMediaContent($ID, $content);
		$this->ShowMemoryUsage("IPS_SetMediaContent '$name'");
		$resultContent = null;
	}

	// -------------------------------------------------------------------------
	private function GetViewProperties ($ID) {
		$viewObj = $this->GetViewObject($ID);
		$name    = IPS_GetName($ID);

		$result                           = array();
		$result['ViewRatio12']            = $viewObj['ViewRatio12'];
		$result['Name']                   = $viewObj['Name'];
		$result['ID']                     = $viewObj['ID'];
		$result['Width']                  = $viewObj['Width'];
		$result['Height']                 = $viewObj['Height'];
		$result['Client']                 = $viewObj['Client'];
		$result['Hardware']               = $viewObj['Hardware'];
		$result['FullScreen']             = $viewObj['FullScreen'];
		$result['RemoteActiveTab']        = $viewObj['RemoteActiveTab'];
		$result['RemoteActivePopup']      = $viewObj['RemoteActivePopup'];
		$result['RemoteInlineMain']       = $viewObj['RemoteInlineMain'];
		$result['RemoteInlineHeader']     = $viewObj['RemoteInlineHeader'];
		$result['RemoteInlineMenu']       = $viewObj['RemoteInlineMenu'];
		$result['RemoteInlineNavigation'] = $viewObj['RemoteInlineNavigation'];
		$result['RemoteInlineDetail']     = $viewObj['RemoteInlineDetail'];
		$result['RemoteInlineContent']    = $viewObj['RemoteInlineContent'];
		$result['RemoteInlineInfo']       = $viewObj['RemoteInlineInfo'];
		$result['RemoteInlinePopup1']     = $viewObj['RemoteInlinePopup1'];
		$result['RemoteInlinePopup2']     = $viewObj['RemoteInlinePopup2'];
		$result['RemoteInlinePopup3']     = $viewObj['RemoteInlinePopup3'];
		$result['ServerActiveTab']        = $viewObj['ServerActiveTab'];
		$result['ServerActivePopup']      = $viewObj['ServerActivePopup'];
		$result['ServerInlineMain']       = $viewObj['ServerInlineMain'];
		$result['ServerInlineHeader']     = $viewObj['ServerInlineHeader'];
		$result['ServerInlineMenu']       = $viewObj['ServerInlineMenu'];
		$result['ServerInlineNavigation'] = $viewObj['ServerInlineNavigation'];
		$result['ServerInlineDetail']     = $viewObj['ServerInlineDetail'];
		$result['ServerInlineContent']    = $viewObj['ServerInlineContent'];
		$result['ServerInlineInfo']       = $viewObj['ServerInlineInfo'];
		$result['ServerInlinePopup1']     = $viewObj['ServerInlinePopup1'];
		$result['ServerInlinePopup2']     = $viewObj['ServerInlinePopup2'];
		$result['ServerInlinePopup3']     = $viewObj['ServerInlinePopup3'];
		if (array_key_exists('ClientWinMinimizeID', $viewObj))
			$result['ClientWinMinimizeID']    = $viewObj['ClientWinMinimizeID'];
		if (array_key_exists('ClientWinBalloonID', $viewObj))
			$result['ClientWinBalloonID']     = $viewObj['ClientWinBalloonID'];
		if (array_key_exists('ClientWinBalloonSec', $viewObj))
			$result['ClientWinBalloonSec']    = $viewObj['ClientWinBalloonSec'];
		if (array_key_exists('ClientAndKeepScreenOnID', $viewObj))
			$result['ClientAndKeepScreenOnID']= $viewObj['ClientAndKeepScreenOnID'];
		if (array_key_exists('ClientAndBrightnessID', $viewObj))
			$result['ClientAndBrightnessID']  = $viewObj['ClientAndBrightnessID'];
		if (array_key_exists('ClientAndBatteryID', $viewObj))
			$result['ClientAndBatteryID']     = $viewObj['ClientAndBatteryID'];
		if (array_key_exists('ClientiOSBatteryID', $viewObj))
			$result['ClientiOSBatteryID']     = $viewObj['ClientiOSBatteryID'];
		if (array_key_exists('FeedbackAudible', $viewObj))
			$result['FeedbackAudible']        = $viewObj['FeedbackAudible'];
		if (array_key_exists('FeedbackHaptic', $viewObj))
			$result['FeedbackHaptic']         = $viewObj['FeedbackHaptic'];
		
		$this->ShowMemoryUsage("getViewProperties '$name'");
		$viewObj = null;

	   return $result;
	}

	// -------------------------------------------------------------------------
	private function GetViewObject($ID) {
 	    $name = IPS_GetName($ID);
	
		// Read Content of View
		$content     = IPS_GetMediaContent($ID);
		$this->ShowMemoryUsage("IPS_GetMediaContent '$name'");
		if ($content===false) {
			die("Media Objekt von Master konnte NICHT gefunden werden");
		}

		$data         = base64_decode($content);
		$this->ShowMemoryUsage("base64_decode '$name'");
		$content      = null;

		$obj          = json_decode($data, true);
		$this->ShowMemoryUsage("json_decode '$name'");
		$data         = null;
	 	if ($obj===false) {
			die("JSON Objekt von View '$name' konnte NICHT dekodiert werden");
		}
		return $obj;
	}

	// -------------------------------------------------------------------------
	private function ResizeView ($jsonObj, $factorX, $factorY) {
		$factor = $factorX > $factorY ? $factorY : $factorX;
		$pages  = array();
		foreach ($jsonObj['Pages'] as $page) {
		   //echo "Process Page=".$page['DisplayName'].PHP_EOL;
			$controls = array();
			foreach ($page['Controls'] as $control) {
				$control['Width']     = round($control['Width'] * $factorX);
				$control['Height']    = round($control['Height'] * $factorY);
				$control['LocationX'] = round($control['LocationX'] * $factorX);
				$control['LocationY'] = round($control['LocationY'] * $factorY);
				if (array_key_exists('Font', $control) && array_key_exists('Size', $control['Font'])) {
				   $control['Font']['Size'] = round($control['Font']['Size'] * $factor);
				}
				if (array_key_exists('Font', $control) && array_key_exists('DesignerSize', $control['Font'])) {
				   $control['Font']['DesignerSize'] = round($control['Font']['DesignerSize'] * $factor);
				}
				if (array_key_exists('CR1', $control)) $control['CR1'] = round($control['CR1'] * $factor);
				if (array_key_exists('CR2', $control)) $control['CR2'] = round($control['CR2'] * $factor);
				if (array_key_exists('CR3', $control)) $control['CR3'] = round($control['CR3'] * $factor);
				if (array_key_exists('CR4', $control)) $control['CR4'] = round($control['CR4'] * $factor);
				$controls[] = $control;
			}
			$page['Controls']    = $controls;
			$page['PopupWidth']  = round($page['PopupWidth'] * $factorX);
			$page['PopupHeight'] = round($page['PopupHeight'] * $factorY);
			$pages[] = $page;
		}
		if (array_key_exists('DefaultFontSize', $jsonObj)) $jsonObj['DefaultFontSize'] = round($jsonObj['DefaultFontSize'] * $factor);
		$jsonObj['Pages'] = $pages;

		return $jsonObj;
	}

	// -------------------------------------------------------------------------
	private function ShowMemoryUsage($statusMessage) {
		$statusMessage = substr($statusMessage.'                                                            ', 0, 60);
		$memory = round(memory_get_usage() / 1024 / 1024, 2);
		$this->SendDebug("Resize", $statusMessage . ' UsedMemory='.$memory. " MB", 0);
	}
	
	
	
}

?>
