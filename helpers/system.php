<?php
/**
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 *
 * @copyright   Copyright (C) 2012 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Generated by AKHelper - http://asikart.com
 */


// No direct access
defined('_JEXEC') or die;

/**
 * Handle some component system information.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperSystem
{
    /**
     * A cache to store component system config (Not Joomla! component params).
     *
     * @var array 
     */
    static $config  = array();
    
    /**
     * Version of component.
     *
     * @var array 
     */
    static $version = array();
    
    /**
     * Profiler store.
     *
     * @var array 
     */
    static $profiler = array() ;
    
    /**
     * Buffer to save last Profiler logs form UserState.
     *
     * @var array 
     */
    static $state_buffer = array();
    
    /**
     * Generate UUID v4 or v5.
     *
     * Ref from: https://gist.github.com/dahnielson/508447
     * 
     * @param   mixed   The condition to generate v3 MD5 UUID, may be string or array.
     *                  If this params is null, will generate v4 random UUID.
     *                  When condition exists, output will always the same.
     * @param   mixed   UUID version. May be integer 5 or string 'v5',
     *                  others will retuen v4 random uuid or v3 md5 uuid(if $condition exists).
     *
     * @return  string  32bit UUID.
     */
    public static function uuid($condition = null, $version = 4)
    {
        $uuid = '' ;
        
        // Generate UUID v4 By Random
        if(!$condition) {
            
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
     
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
         
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
         
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
         
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
         
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
        }else{
            // Genertae UUID v3 By Condition MD5
            $condition = (array) $condition ;
            $condition = implode( '-', $condition );
            
            $chars = md5( $condition );
            $uuid  = substr($chars,0,8) . '-';
            $uuid .= substr($chars,8,4) . '-';
            $uuid .= substr($chars,12,4) . '-';
            $uuid .= substr($chars,16,4) . '-';
            $uuid .= substr($chars,20,12);
            
        }
        
        // Generate UUID v5
        if( $version == 5 || $version == 'v5' ) {
            if(preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) !== 1 ) {
                return $uuid ;
            }
            
            // Get hexadecimal components of namespace
            $nhex = str_replace(array('-','{','}'), '', $uuid);
            
            // Binary Value
            $nstr = '';
            
            // Convert Namespace UUID to bits
            for($i = 0; $i < strlen($nhex); $i+=2) 
            {
                $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
            }
            
            // Calculate hash value
            if( $condition ) {
                $condition = is_array($condition) ? implode('-', $condition) : $condition ; 
            }else{
                $condition = uniqid();
            }
            $hash = sha1($nstr . $condition);
            
            $uuid = sprintf('%08s-%04s-%04x-%04x-%12s',
                
                // 32 bits for "time_low"
                substr($hash, 0, 8),
                
                // 16 bits for "time_mid"
                substr($hash, 8, 4),
                
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 5
                (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
                
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
                
                // 48 bits for "node"
                substr($hash, 20, 12)
            );
            
        }
        
        return $uuid ;
    }
    
     /**
     * Get component Joomla! params, a proxy of JComponentHelper::getParams($option) ;
     * 
     * @param   string     $option Component option name.
     *
     * @return  JRegistry  Component params object.
     */
    public static function getParams($option = null)
    {
        if(!$option) {
            $option = AKHelper::_('path.getOption') ;
        }
        
        if($option) {
            return JComponentHelper::getParams($option);
        }
    }
    
    /**
     * Get component system config, if first param not exists, will return all params object.
     * 
     * @param   string    $key        Param key.
     * @param   string    $default    Default value if key not exists.
     * @param   string    $option     Component option name.
     *
     * @return  mixed    Param value.    
     */
    public static function getConfig($key = null, $default = null, $option = null)
    {
        if(!$option){
            $option = AKHelper::_('path.getOption') ;
        }
        
        // Singleton & Lazy loading
        if(isset(self::$config[$option])) {
            if(!$key){
                return self::$config[$option] ;
            }else{
                return self::$config[$option]->get($key, $default) ;
            }
        }
        
        // Init Config
        self::$config[$option] = new JRegistry();
        self::$config[$option]->loadFile( AKHelper::_('path.getAdmin', $option).'/includes/config.json' );
        
        if(!$key){
            return self::$config[$option] ;
        }else{
            return self::$config[$option]->get($key, $default) ;
        }
    }
    
    /**
     * Save component params to #__extension.
     * 
     * @param   mixed    $params    A params object, array or JRegistry object.
     * @param   string   $element   Extension element name, eg: com_content, mod_modules.
     * @param   string   $client    Client, 1 => 'site', 2 => 'administrator'.
     * @param   string   $group     Group(folder) name for plugin.
     *
     * @return  boolean    Success or not.
     */
    public static function saveParams($params, $element, $client = null, $group = null)
    {
        if( $params instanceof JRegistry ) {
            $params = (string) $params ;
        }else{
            $params = json_decode($params) ;
        }
        
        $client = ($client == 'admin' || $client == 1) ? 1 : 0 ;
        
        $db = JFactory::getDbo();
        $q = $db->getQuery(true) ;
        
        if( $client ) {
            $q->where("client_id = '{$client}'") ;
        }
        
        if( $group ) {
            $q->where("folder = '{$group}'");
        }
        
        $q->update( '#__extensions' )
            ->set("params = '{$params}'")
            ->where("element = '{$element}'")
            ;
        
        $db->setQuery($q);
        return $db->execute();
    }
    
    /**
     * Save component config to "config.json" in includes dir.
     * 
     * @param   mixed   $params     A config object, array or JRegistry object.
     * @param   string  $option     Component option name.
     *
     * @return  boolean    Success or not.    
     */
    public static function saveConfig($params, $option = null)
    {
        if( $params instanceof JRegistry ) {
            $params = (string) $params ;
        }else{
            $params = json_decode($params) ;
        }
        
        $path = AKHelper::_('path.getAdmin', $option) . '/includes/config.json' ;
        return JFile::write($path, $params) ;
    }
    
    /**
     * Get component version form manifest XML file.
     * 
     * @param   string    $option    Component option name.
     *
     * @return  string    Component version.
     */
    public static function getVersion($option = null)
    {
        if(!$option){
            $option = AKHelper::_('path.getOption') ;
        }
        
        if(isset(self::$version[$option])) {
            return self::$version[$option] ;
        }
        
        $xml = AKHelper::_('path.getAdmin').'/'.substr(AKHelper::_('path.getOption'), 4).'.xml' ;
        $xml = JFactory::getXML($xml, true) ;
        
        return self::$version[$option] = $xml->version ;
    }
    
    /**
     * A helper to add JProfiler log mark. Need to trun on the debug mode.
     * 
     * @param   string    $text         Log text.
     * @param   string    $namespace    The JProfiler instance ID. Default is the core profiler "Application". 
     */
    public static function mark($text, $namespace = null)
    {
        $app = JFactory::getApplication() ;
        if(!$namespace) {
            $namespace = 'Application' ;
        }
        
        if( !(JDEBUG && $namespace == 'Application') && !AKDEBUG) {
            return ;
        }
        
        if(!isset(self::$profiler[$namespace])) {
            jimport('joomla.error.profiler');
            self::$profiler[$namespace] = JProfiler::getInstance($namespace);
            
            // Get last page logs.
            self::$state_buffer = $app->getUserState('windwalker.system.profiler.'.$namespace);
        }
        
        
        self::$profiler[$namespace]->mark($text) ;
        
        // Save in session
        $app->setUserState('windwalker.system.profiler.'.$namespace, self::$profiler[$namespace]->getBuffer());
    }
    
    /**
     * Render the profiler log data, and echo it..
     * 
     * @param   string    $namespace    The JProfiler instance ID. Default is the core profiler "Application".  
     */
    public static function renderProfiler($namespace = null)
    {
        $app = JFactory::getApplication() ;
        
        if(!$namespace) {
            $namespace = 'Application' ;
        }
        
        $buffer = 'No Profiler data.';
        
        if(isset(self::$profiler[$namespace])) {
            $_PROFILER = self::$profiler[$namespace] ;
            
            $buffer = $_PROFILER->getBuffer();
            $buffer = implode("\n<br />\n", $buffer) ;
        }else{
            $buffer = $app->getUserState('windwalker.system.profiler.'.$namespace);
            $buffer = $buffer ? implode("\n<br />\n", $buffer) : '';
        }
        
        $buffer = $buffer ? $buffer : 'No Profiler data.';
        
        // Get last page logs
        $state_buffer = self::$state_buffer ;
        
        if($state_buffer) {
            $state_buffer = implode("\n<br />\n", $state_buffer) ;
            $buffer = $state_buffer . "\n<br />---------<br />\n" . $buffer ;
        }
        
        // Render
        $buffer = "<pre><h3>WindWalker Debug [namespace: {$namespace}]: </h3>".$buffer.'</pre>' ;
        
        $app->setUserState('windwalker.system.profiler.'.$namespace, '');
        
        echo $buffer ;
    }
    
    /**
     * Get API SDK For AKApi System.
     */
    public static function getApiSDK( $host = null )
    {
        static $instance ;
        
        if($instance) {
            return $instance ;
        }else{
            include_once SCHEDULE_ADMIN.'/class/customersdk/customersdk.php' ;
            
            $instance = $service = AKApiSDK::getInstance($host);
            
            return $service;
        }
        
    }
}