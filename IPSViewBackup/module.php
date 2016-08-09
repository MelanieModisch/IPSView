<?
require_once(__DIR__ . DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."IPSViewBase.php"); 

class IPSViewBackup extends IPSViewBase
{
	
	// -------------------------------------------------------------------------
	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyInteger("View", 0);
		$this->RegisterPropertyString("Directory", "backup/");

		$this->RegisterPropertyBoolean("AutoBackup", true);		
		$this->RegisterPropertyInteger("Interval", 10);
		$this->RegisterTimer("CheckViewBackupTimer", 0, 'IPSView_CheckViewBackup($_IPS[\'TARGET\']);');

		$this->RegisterPropertyBoolean("AutoPurge", true);		
		$this->RegisterPropertyInteger("PurgeDays", 30);
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
			if ($this->ReadPropertyBoolean("AutoBackup")) {
				$this->SetTimerInterval("CheckViewBackupTimer", $this->ReadPropertyInteger("Interval")*60*1000);
			} else {
				$this->SetTimerInterval("CheckViewBackupTimer", 0);
			}
		
			$this->SetStatus(102); //Instanz ist aktiv
		} else {
			$this->SetTimerInterval("CheckViewBackupTimer", 0);
			$this->SetStatus(104); //Instanz ist inaktiv
		}
	}
	
	// -------------------------------------------------------------------------
	public function Backup()
	{		
		if ($this->IsInstancePropertiesValid()) {
			$viewID        = $this->ReadPropertyInteger('View');
			$directory     = $this->GetBackupDirectory();
			$media         = IPS_GetMedia($viewID);
			$mediaFile     = IPS_GetKernelDir().$media['MediaFile'];
			$backupFile    = $directory.$viewID.'__'.date('Ymd_Hi').'.ipsView';
			
			$this->SendDebug("Backup", "Create Backup for View From File=$mediaFile to File=.$backupFile", 0);
			copy ($mediaFile, $backupFile);
		}
	}

	// -------------------------------------------------------------------------
	public function CheckViewBackup()
	{
		$this->SendDebug("CheckView", "Check View ...", 0);
		
		if ($this->IsInstancePropertiesValid()) {
			$viewID        = $this->ReadPropertyInteger('View');
			$directory     = $this->GetBackupDirectory();
			$media         = IPS_GetMedia($viewID);
			$mediaFile     = IPS_GetKernelDir().$media['MediaFile'];
			$mediaContent  = file_get_contents($mediaFile);
			$backupContent = $this->GetLastBackupContent($directory, $viewID);

			if ($mediaContent != $backupContent) {
				$this->SendDebug("Backup", "Found View Modification for View with ID=$viewID", 0);
				$this->Backup();
				$this->PurgeLogFiles($directory, $viewID, $days);
			}
			
			$this->SetTimerInterval("CheckViewBackupTimer", $this->ReadPropertyInteger("Interval")*60*1000);
		} else {
			$this->SetTimerInterval("CheckViewBackupTimer", 0);
			$this->SetStatus(104); //Instanz ist inaktiv
		}		
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------
	private function GetBackupDirectory()
	{		
		$result  = $this->ReadPropertyString('Directory');
		if (is_dir($result)) {
			return $result;
		}
		$result  = IPS_GetKernelDir().$result;
		if (is_dir($result)) {
			return $result;
		}
		return $this->ReadPropertyString('Directory');	
	}
	
	// -------------------------------------------------------------------------
	private function IsInstancePropertiesValid()
	{		
		$viewID        = $this->ReadPropertyInteger('View');
		$directory     = $this->GetBackupDirectory();
		
		return IPS_MediaExists($viewID) && is_dir($directory );
	}

	// -------------------------------------------------------------------------
	function GetLastBackupContent ($directory, $id) {
		$result   = '';
		$lastFile = '';
		if (($handle=opendir($directory))===false) {
			die ('Error Opening Directory '.$directory);
		}

		while (($file = readdir($handle))!==false) {
			$fileID        = substr($file, 0, 5);
			if ($file > $lastFile and $fileID==$id) {
				$lastFile = $file;
			}
		}
		closedir($handle);
		if ($lastFile > '') {
			$result   = file_get_contents($directory.$lastFile);
		}
		
		return $result;
	}

	// -------------------------------------------------------------------------
	function PurgeBackupFiles($directory, $id, $days) {
		$referenceDate=Date('Ymd', strtotime("-".$days." days"));
		$this->SendDebug("Purge", "Purge IPSView Backupfile older $referenceDate", 0);

		if (($handle=opendir($directory))===false) {
			die ('Error Opening Directory '.$directory);
		}

		while (($file = readdir($handle))!==false) {
			$fileDate      = substr($file, 7, 8);
			$fileID        = substr($file, 0, 5);
			if ($fileDate < $referenceDate and $fileID==$id) {
				$this->SendDebug("Purge", "Purge IPSView Backupfile $file", 0);
				unlink($directory.$file);
			}
		}
		closedir($handle);
	}

}

?>
