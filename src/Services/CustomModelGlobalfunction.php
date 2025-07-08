<?php

namespace Drupal\custom_model\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Service;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomModelGlobalFunction{

public function custom_model_ideas_files_path()
{
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/custom_model_uploads/ideas_files/';
}

 public function custom_model_path()
  {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/custom_model_uploads/';
  }
 public function default_value_for_uploaded_files($filetype, $proposal_id)
{
	$query = \Drupal::database()->select('custom_model_submitted_abstracts_file');
	$query->fields('custom_model_submitted_abstracts_file');
	$query->condition('proposal_id', $proposal_id);
	$selected_files_array = "";
	if ($filetype == "A")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	} //$filetype == "A" A-abstract
	elseif ($filetype == "P")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	}// P - Script file
	elseif ($filetype == "S")
	{
		$query->condition('filetype', $filetype);
		$filetype_q = $query->execute()->fetchObject();
		return $filetype_q;
	} //S - Simulation file
	else
	{
		return;
	}
	return;

}

 public function _cm_list_of_departments()
{
    $department = array();
    $query = \Drupal::database()->select('custom_model_list_of_departments');
    $query->fields('custom_model_list_of_departments');
    $query->orderBy('id', 'ASC');
    $department_list = $query->execute();
    while ($department_list_data = $department_list->fetchObject())
    {
        $department[$department_list_data->department] = $department_list_data->department;
    } //$department_list_data = $department_list->fetchObject()
    return $department;
}

 public function _list_of_software_versions()
{
    $software_version = array();
    $query = \Drupal::database()->select('dwsim_software_version');
    $query->fields('dwsim_software_version');
    $query->orderBy('id', 'ASC');
    $software_version_list = $query->execute();
    while ($software_version_list_data = $software_version_list->fetchObject())
    {
        $software_version[$software_version_list_data->dwsim_version] = $software_version_list_data->dwsim_version;
    } //$software_version_list_data = $software_version_list->fetchObject()
    return $software_version;
}

public function _cm_list_of_states()
{
    $states = array(
        0 => '-Select-'
    );
    $query = \Drupal::database()->select('list_states_of_india');
    $query->fields('list_states_of_india');
    //$query->orderBy('', '');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject())
    {
        $states[$states_list_data->state] = $states_list_data->state;
    } //$states_list_data = $states_list->fetchObject()
    return $states;
}

public function _cm_list_of_cities()
{
    $city = array(
        0 => '-Select-'
    );
    $query = \Drupal::database()->select('list_cities_of_india');
    $query->fields('list_cities_of_india');
    $query->orderBy('city', 'ASC');
    $city_list = $query->execute();
    while ($city_list_data = $city_list->fetchObject())
    {
        $city[$city_list_data->city] = $city_list_data->city;
    } //$city_list_data = $city_list->fetchObject()
    return $city;
}
public function _cm_list_of_pincodes()
{
    $pincode = array(
        0 => '-Select-'
    );
    $query = \Drupal::database()->select('list_of_all_india_pincode');
    $query->fields('list_of_all_india_pincode');
    $query->orderBy('pincode', 'ASC');
    $pincode_list = $query->execute();
    while ($pincode_list_data = $pincode_list->fetchObject())
    {
        $pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
    } //$pincode_list_data = $pincode_list->fetchObject()
    return $pincode;
}

public function _cm_dir_name($project, $proposar_name)
{

    $project_title = ucname($project);
    $proposar_name = ucname($proposar_name);
    $dir_name = $project_title . ' By ' . $proposar_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", " ", $dir_name)));
    return $directory_name;
}
public function cm_RenameDir($proposal_id, $dir_name)
{
    $proposal_id = $proposal_id;
    $dir_name = $dir_name;
    $query = \Drupal::database()->query("SELECT directory_name,id FROM custom_model_proposal WHERE id = :proposal_id", array(
        ':proposal_id' => $proposal_id
    ));
    $result = $query->fetchObject();
    if ($result != NULL)
    {
        $files = scandir(custom_model_path());
        $files_id_dir = custom_model_path() . $result->id;
        //var_dump($files);die;
        $file_dir = custom_model_path() . $result->directory_name;
        if (is_dir($file_dir))
        {
            $new_directory_name = rename(custom_model_path() . $result->directory_name, custom_model_path() . $dir_name);
            return $new_directory_name;
        } //is_dir($file_dir)
        else if (is_dir($files_id_dir))
        {
            $new_directory_name = rename(custom_model_path() . $result->id, custom_model_path() . $dir_name);
            return $new_directory_name;
        } //is_dir($files_id_dir)
        else
        {
            \Drupal::messenger()->addMessage('Directory not available for rename.');
            return;
        }
    } //$result != NULL
    else
    {
        \Drupal::messenger()->addMessage('Project directory name not present in databse');
        return;
    }
    //var_dump($files);die;
    /* if ($files != NULL)
    {
    $new_directory_name = rename(custom_model_path() . $result->directory_name, custom_model_path() . $dir_name) or \Drupal::messenger()->addMessage("Unable to rename folder");
    }
    else
    {
    $new_directory_name = 'Can not rename the directory. Directory not present';
    }*/
    return;
}
public function CreateReadmeFileCustomModel($proposal_id)
{
    $result = \Drupal::database()->query("
                        SELECT * from custom_model_proposal WHERE id = :proposal_id", array(
        ":proposal_id" => $proposal_id
    ));
    $proposal_data = $result->fetchObject();
    $root_path = custom_model_path();
    $readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "About the Custom Model";
    $txt .= "\n" . "\n";
    $txt .= "Title Of The Custom Model Project: " . $proposal_data->project_title . "\n";
    $txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
    $txt .= "University: " . $proposal_data->university . "\n";
    $txt .= "\n" . "\n";
    $txt .= "OM PSSP Project By FOSSEE, IIT Bombay" . "\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
    return $txt;
}

public function cm_rrmdir_project($prop_id)
{
    $proposal_id = $prop_id;
    $result = \Drupal::database()->query("
                    SELECT * from custom_model_proposal WHERE id = :proposal_id", array(
        ":proposal_id" => $proposal_id
    ));
    $proposal_data = $result->fetchObject();
    $root_path = custom_model_path();
    $dir = $root_path . $proposal_data->directory_name;
    if ($proposal_data->id == $prop_id)
    {
        if (is_dir($dir))
        {
            $objects = scandir($dir);
            foreach ($objects as $object)
            {
                if ($object != "." && $object != "..")
                {
                    if (filetype($dir . "/" . $object) == "dir")
                    {
                        cm_rrmdir($dir . "/" . $object);
                    } //filetype($dir . "/" . $object) == "dir"
                    else
                    {
                        unlink($dir . "/" . $object);
                    }
                } //$object != "." && $object != ".."
            } //$objects as $object
            reset($objects);
            rmdir($dir);
            $msg = \Drupal::messenger()->addMessage("Directory deleted successfully");
            return $msg;
        } //is_dir($dir)
        $msg = \Drupal::messenger()->addMessage("Directory not present");
        return $msg;
    } //$proposal_data->id == $prop_id
    else
    {
        $msg = \Drupal::messenger()->addMessage("Data not found");
        return $msg;
    }
}

public function cm_rrmdir($dir)
{
    if (is_dir($dir))
    {
        $objects = scandir($dir);
        foreach ($objects as $object)
        {
            if ($object != "." && $object != "..")
            {
                if (filetype($dir . "/" . $object) == "dir")
                    cm_rrmdir($dir . "/" . $object);
                else
                    unlink($dir . "/" . $object);
            } //$object != "." && $object != ".."
        } //$objects as $object
        reset($objects);
        rmdir($dir);
    } //is_dir($dir)
}

public function custom_model_get_proposal()
  {
    global $user;
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {custom_model_proposal} WHERE solution_provider_uid = ".$user->uid." AND solution_status = 2 ORDER BY id DESC LIMIT 1");
    $query = \Drupal::database()->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data)
      {
        \Drupal::messenger()->addMessage("You do not have any approved custom model proposal. Please check the ");
        drupal_goto('');
      }
    switch ($proposal_data->approval_status)
    {
        case 0:
            \Drupal::messenger()->addMessage(t('Proposal is awaiting approval.'), 'status');
            return FALSE;
        case 1:
            return $proposal_data;
        case 2:
            \Drupal::messenger()->addMessage(t('Proposal has been dis-approved.'), 'error');
            return FALSE;
        case 3:
            \Drupal::messenger()->addMessage(t('Proposal has been marked as completed.'), 'status');
            return FALSE;
        default:
            \Drupal::messenger()->addMessage(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
            return FALSE;
    }
    return FALSE;
}

public function custom_model_ucname($string)
  {
    $string = ucwords(strtolower($string));
    foreach (array(
        '-',
        '\''
    ) as $delimiter)
      {
        if (strpos($string, $delimiter) !== false)
          {
            $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
          }
      }
    return $string;
  }

  
}