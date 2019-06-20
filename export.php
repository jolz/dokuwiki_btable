<?php
    
    /**
     * Exports the saved information of the plugin to an csv file
     *
     * Parameters:
     * - id        The id of the plugin instance which should be exported
     */
    
    
    // Farmpatch: 
    // $tmp = explode('/',$_SERVER['SCRIPT_FILENAME']); array_pop($tmp); array_pop($tmp); 
    // if(!defined('DOKU_INC')) define('DOKU_INC',dirname(join('/',$tmp)).'/../');

    if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
    if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
    if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');
    
    require_once(DOKU_CONF."dokuwiki.php");
    require_once(DOKU_INC."inc/utf8.php");
    
    // set metadir acording to config (see inc/init.php)
    $conf['metadir'] = $conf['savedir'].'/meta';
    
    
    // read parameters
    $dID = $_GET['id'];
    
    
    // get doodle file contents
    $file = DOKU_INC.metaFN(md5($dID), '.btable');
    $content = unserialize(@file_get_contents($file));
    
    // write out header
	  header("Content-type: text/csv");
	  header("Content-Disposition: attachment; filename=".$dID.".csv");
    
    // write out content
    if (is_array($content)) {
        
        $rows = array_keys($content);
        $columns = array_keys($content[$rows[0]]);
        
        if (count($columns) > 0) {
            
            foreach($columns as $column) {
                echo(",".$column);
            }
            
            echo("\n");
            
            foreach ($rows as $row) {
                
                echo($row);
                
                foreach ($columns as $column) {
                    
                    if ($content[$row][$column]) {
                        echo(",1");
                    } else {
                        echo(",0");
                    }
                }
                
                echo("\n");
            }
        }
    }
    
    
    
    
    /**
     * Remove unwanted chars from ID
     *
     * Cleans a given ID to only use allowed characters. Accented characters are
     * converted to unaccented ones
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param  string  $raw_id    The pageid to clean
     * @param  boolean $ascii     Force ASCII
     * @see inc/pageutils.php
     */
    function cleanID($raw_id,$ascii=false){
        global $conf;
        static $sepcharpat = null;

        global $cache_cleanid;
        $cache = & $cache_cleanid;

        // check if it's already in the memory cache
        if (isset($cache[$raw_id])) {
            return $cache[$raw_id];
        }

        $sepchar = $conf['sepchar'];
        if($sepcharpat == null) // build string only once to save clock cycles
            $sepcharpat = '#\\'.$sepchar.'+#';

        $id = trim($raw_id);
        $id = utf8_strtolower($id);

        //alternative namespace seperator
        $id = strtr($id,';',':');
        if($conf['useslash']) {
            $id = strtr($id,'/',':');
        }else{
            $id = strtr($id,'/',$sepchar);
        }

        if($conf['deaccent'] == 2 || $ascii) $id = utf8_romanize($id);
        if($conf['deaccent'] || $ascii) $id = utf8_deaccent($id,-1);

        //remove specials
        $id = utf8_stripspecials($id,$sepchar,'\*');

        if($ascii) $id = utf8_strip($id);

        //clean up
        $id = preg_replace($sepcharpat,$sepchar,$id);
        $id = preg_replace('#:+#',':',$id);
        $id = trim($id,':._-');
        $id = preg_replace('#:[:\._\-]+#',':',$id);

        $cache[$raw_id] = $id;
        
        return($id);
    }

    
    /**
     * returns the full path to the meta file specified by ID and extension
     *
     * The filename is URL encoded to protect Unicode chars
     *
     * @author Steven Danz <steven-danz@kc.rr.com>
     * @see inc/pageutils.php
     */
    function metaFN($id,$ext){
        global $conf;
      
        $id = cleanID($id);
        $id = str_replace(':','/',$id);
        $fn = $conf['metadir'].'/'.utf8_encodeFN($id).$ext;
        
        return $fn;
    }
?>
