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
		
		$viewID        = $this->ReadPropertyInteger('View');
		$autoBackup    = $this->ReadPropertyBoolean('AutoBackup');
		$directory     = $this->GetBackupDirectory();
		
		if (!IPS_MediaExists($viewID)) {
			$this->SetStatus(203); //No View defined
		} else if (trim($directory)=="") {
			$this->SetStatus(201); //No Folder
		} else if (!is_dir($directory )) {
			$this->SetStatus(202); //Folder NOT found
		} else if (!$autoBackup) {
			$this->SetStatus(104); //Instanz ist inaktiv
		} else {		
			$this->SetStatus(102); //Instanz ist aktiv
		}

		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];
		if ($status==102 && $autoBackup) {
			$this->SetTimerInterval("CheckViewBackupTimer", $this->ReadPropertyInteger("Interval")*60*1000);
		} else {
			$this->SetTimerInterval("CheckViewBackupTimer", 0);
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
		
		$result = false;
		if ($this->IsInstancePropertiesValid()) {
			$viewID        = $this->ReadPropertyInteger('View');
			$autoPurge     = $this->ReadPropertyBoolean('AutoPurge');
			$directory     = $this->GetBackupDirectory();
			$media         = IPS_GetMedia($viewID);
			$mediaFile     = IPS_GetKernelDir().$media['MediaFile'];
			$mediaContent  = file_get_contents($mediaFile);
			$backupContent = $this->GetBackupContentLast($directory, $viewID);

			if ($mediaContent != $backupContent) {
				$this->SendDebug("Backup", "Found View Modification for View with ID=$viewID", 0);
				$this->Backup();
				if ($autoPurge) {
					$this->PurgeBackupFiles();
				}
				$result = true;
			}
			
			$this->SetTimerInterval("CheckViewBackupTimer", $this->ReadPropertyInteger("Interval")*60*1000);
		}
		return $result;		
	}

	// -------------------------------------------------------------------------
	public function PurgeBackupFiles()
	{	
		$viewID        = $this->ReadPropertyInteger('View');
		$days          = $this->ReadPropertyInteger('PurgeDays');
		$directory     = $this->GetBackupDirectory();
		
		$this->PurgeBackupFilesByDays($directory, $viewID, $days);
	}

	
	// -------------------------------------------------------------------------
	public function RestoreByFileName($file)
	{	
		return $this->Restore($file);
	}

	// -------------------------------------------------------------------------
	public function RestoreByFileIdx($idx)
	{	
		$viewID        = $this->ReadPropertyInteger('View');
		$directory     = $this->GetBackupDirectory();
		$file          = $this->GetBackupFileIdx($directory, $viewID, $idx);

		return $this->Restore($file);
	}

	// -------------------------------------------------------------------------
	public function GetBackupFiles()
	{	
		$viewID        = $this->ReadPropertyInteger('View');
		$directory     = $this->GetBackupDirectory();
		if (($handle=opendir($directory))===false) {
			die ('Error Opening Directory '.$directory);
		}

		$files = array();
		while (($file = readdir($handle))!==false) {
			$fileID        = substr($file, 0, 5);
			if ($fileID==$viewID) {
				$files[] = $file;
			}
		}
		closedir($handle);

		$count  = 0;
		$result = array();
		$files  = array_reverse($files);
		$this->SendDebug("ShowFiles",  "Files: ", 0);
		foreach($files as $file) {
			$count++;
			$result[$count] = $file;
			$this->SendDebug("ShowFiles",  "$count = $file", 0);
		}
		return $result;
	}

	// ----------------------------------------------------------------------------------------------------
	// PRIVATE Functions
	// ----------------------------------------------------------------------------------------------------

	// -------------------------------------------------------------------------
	private function Restore($file)
	{	
		if (!$this->IsInstancePropertiesValid()) {
			$this->SendDebug("Restore", "Backup Settings not valid!", 0);
			return false;
		}			

		if ($file == '') {
			$this->SendDebug("Restore", "No Backup found!", 0);
			return false;
		}
	
		$viewID        = $this->ReadPropertyInteger('View');
		$directory     = $this->GetBackupDirectory();
		$backupFile    = $directory.$file;
		$mediaFile     = IPS_GetKernelDir().'media/'.$viewID.'.ipsView';
	
		if (!file_exists($backupFile)) {
			$this->SendDebug("Restore", "Backupfile NOT fould!", 0);
			return false;
		}

		if (copy ($backupFile, $mediaFile)) {
			$this->SendDebug("Restore",  "Restored $backupFile", 0);
			return true;
		} else {
			$this->SendDebug("Restore",  "Restored $backupFile failed!", 0);
			return false;
		}	
	}

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
		$instance = IPS_GetInstance($this->InstanceID);
		$status   = $instance['InstanceStatus'];

		return ($status == 102 || $status == 104);
	}

	// -------------------------------------------------------------------------
	private function GetBackupContentLast ($directory, $id) {
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
	private function GetBackupFileIdx($directory, $id, $idx) {
		$result   = '';
		if (($handle=opendir($directory))===false) {
			die ('Error Opening Directory '.$directory);
		}

		$files = array();
		while (($file = readdir($handle))!==false) {
			$fileID        = substr($file, 0, 5);
			if ($fileID==$id) {
				$files[] = $file;
			}
		}
		closedir($handle);

		$count = 0;
		$files = array_reverse($files);
		$file  = '';
		foreach($files as $item) {
			$count++;
			if ($count==$idx)
				$file = $item;
		}
				
		return $file;
	}

	// -------------------------------------------------------------------------
	private function PurgeBackupFilesByDays($directory, $id, $days) {
		$referenceDate=Date('Ymd', strtotime("-".$days." days"));
		$this->SendDebug("Purge", "Purge IPSView Backupfiles older Date=$referenceDate", 0);

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
