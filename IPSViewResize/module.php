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
		
		if ($this->IsInstancePropertiesValid()) {
			if ($this->ReadPropertyBoolean("AutoSync")) {
				$this->SetTimerInterval("CheckMasterTimer", $this->ReadPropertyInteger("Interval")*1000);
			} else {
				$this->SetTimerInterval("CheckMasterTimer", 0);
			}
		
			$this->SetStatus(102); //Instanz ist aktiv
		} else {
			$this->SetTimerInterval("CheckMasterTimer", 0);
			$this->SetStatus(104); //Instanz ist inaktiv
		}
	}
	
	// -------------------------------------------------------------------------
	public function Resize()
	{
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
			$resultObj['ViewRatio12']    = $viewProperties['ViewRatio12'];
			$resultObj['Name']           = $viewProperties['Name'];
			$resultObj['ID']             = $childViewID;
			$resultObj['Width']          = $viewProperties['Width'];
			$resultObj['Height']         = $viewProperties['Height'];
			$resultObj['Client']         = $viewProperties['Client'];
			$resultObj['Hardware']       = $viewProperties['Hardware'];

			$this->ShowMemoryUsage('Build Result:');
			$masterObj = null;
		   
			// Write Data to View
			$this->WriteViewContent ($childViewID, $resultObj);
			$resultObj = null;
			$this->ShowMemoryUsage('finished:');

			$this->SendDebug("Resize", "=============================================================", 0);
			$this->SendDebug("Resize", "Successfully finished synchronizatio", 0);
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
		$masterViewID     = $this->ReadPropertyInteger('MasterView');
		$childViewID      = $this->ReadPropertyInteger('ChildView');
		$ratioX           = $this->ReadPropertyFloat('RatioX');
		$ratioY           = $this->ReadPropertyFloat('RatioY');
		
		return IPS_MediaExists($masterViewID) && IPS_MediaExists($childViewID) && $ratioX > 0 && $ratioY > 0;
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

		$result                   = array();
		$result['ViewRatio12']    = $viewObj['ViewRatio12'];
		$result['Name']           = $viewObj['Name'];
		$result['ID']             = $viewObj['ID'];
		$result['Width']          = $viewObj['Width'];
		$result['Height']         = $viewObj['Height'];
		$result['Client']         = $viewObj['Client'];
		$result['Hardware']       = $viewObj['Hardware'];
		$this->ShowMemoryUsage("getViewProperties '$name'");

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
