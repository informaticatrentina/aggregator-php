<?php
class MY_Exceptions extends CI_Exceptions 
{
  public function __construct()
  {
    parent::CI_Exceptions();
  }
  
  public function show_php_error($severity, $message, $filepath, $line)
  {	
    $severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
    $filepath = str_replace("\\", "/", $filepath);
	
    // For safety reasons we do not show the full file path
    if (FALSE !== strpos($filepath, '/'))
  	{
            $x = explode('/', $filepath);
	    $filepath = $x[count($x)-2].'/'.end($x);
    }
		
    if (ob_get_level() > $this->ob_level + 1)
    {
      ob_end_flush();	
    }
    ob_start();
    include(APPPATH.'errors/html/error_php'.EXT);
    $buffer = ob_get_contents();
    ob_end_clean();
    
    $CI = &get_instance();
    
    if($CI->config->item('aggregator_debug'))
    {
      $msg = 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line;
      log_message('error', $msg , TRUE);
      mail($CI->config->item('aggregator_debug_email'), 'An Error Occurred', $msg, 'From: debug@jlbbooks.it');	
    }  
	}
}