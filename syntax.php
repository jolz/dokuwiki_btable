<?php
/**
 * Boolean Table Plugin 2 (Extended/Modified Doodle Plugin)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     jolZ <jolz@freenet.de>
 *             Oliver Horst <oliver.horst@uni-dortmund.de>  
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_btable extends DokuWiki_Syntax_Plugin {
    
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Jolz, Oliver Horst',
            'email'  => 'jolz@freenet.de',
            'date'   => '2019-06-20',
            'name'   => 'Boolean Table 2 (modified Doodle Plugin)',
            'desc'   => 'Successor of btable plugin, doodle-like polls without authentication.',
            'url'    => 'http://wiki.splitbrain.org/plugin:btable2',
        );
    }
    
    function getType(){ return 'substition';}
    function getPType(){ return 'block';}
    function getSort(){ return 167; }
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode){
        $this->Lexer->addSpecialPattern('<btable.*?>.+?</btable>', $mode, 'plugin_btable');
    }
    
    
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        
        // strip markup
        $match = substr($match, 8, -9);
        
        // split into title and options
        list($title, $options) = preg_split('/>/u', $match, 2);
        
        // check if no title was specified
        if (!$options){
            $options = $title;
            $title   = NULL;
        }
        
        // split into ids and dates part
        list($first, $second) = preg_split('#(\s|\n|\r)*<\/columns>(\s|\n|\r)*<rows>(\s|\n|\r)*#u', $options);
        
        // get ids and dates
        list(, $ids) = preg_split('#(\S|\s|\n|\r)*<columns>(\s|\n|\r)*#u', $first);
        list($dates) = preg_split('#(\s|\n|\r)*<\/rows>(\s|\n|\r)*#u', $second);
        
        $ids = explode('^', $ids);
        $dates = explode('^', $dates);
        
        // remove whitespaces
        for($i = 0; $i < count($ids); $i++) {
            $ids[$i] = trim($ids[$i]);
        }
        
        for($i = 0; $i < count($dates); $i++) {
            $dates[$i] = trim($dates[$i]);
        }
        
        
        return array(trim($title), $ids, $dates);
    }
    
    
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        
        if ($mode == 'xhtml') {
            
            global $ID;
            global $INFO;

            
            $conf_groups = trim($this->getConf('btable_groups'));
            
            $user_groups = $INFO['userinfo']['grps'];
            $plugin_groups = split(';', $conf_groups);
            
            if ((strlen($conf_groups) > 0) && (count($plugin_groups) > 0)) {
                if (isset($user_groups) && is_array($user_groups)) {
                    $write_access = count(array_intersect($plugin_groups, $user_groups));
                } else {
                    $write_access = 0;
                }
            } else {
                $write_access = 1;
            }
            
            
            $title = $renderer->_xmlEntities($data[0]);
            $dID = cleanID($title);
            
            $rows = $data[2];
            $columns = $data[1];
            
            $rows_count = count($rows);
            $columns_count = count($columns);
            
            
            // prevent caching to ensure the poll results are fresh
            $renderer->info['cache'] = false;
            
            // get doodle file contents
            $dfile = metaFN(md5($dID), '.btable');
            $doodle = unserialize(@file_get_contents($dfile));
            
            if ($columns_count == 0) {
                // no rows given: reset the doodle
                $doodle = NULL;
            }
            
            // render form
            $renderer->doc  .= '<form id="btable__form__'.$dID.'" '.
                                    'method="post" '.
                                    'action="'.script().'" '.
                                    'accept-charset="'.$this->getLang('encoding').'">';

            $renderer->doc .= '    <input type="hidden" name="do" value="show" />';
            $renderer->doc .= '    <input type="hidden" name="id" value="'.$ID.'" />';

            if (($submit = $_REQUEST[$dID.'-add']) && $write_access) {
                
                // user has changed/added values -> update results
                
                $row = trim($_REQUEST['row']);
                $change_row = "";
                
                if (!empty($row)){
                
                    for ($i = 0; $i < $columns_count; $i++) {
                        
                        $column = $renderer->_xmlEntities($columns[$i]);
                        
                        if ($_REQUEST[$dID.'-column'.$i]) {
                            $doodle[$row][$column] = true;
                        } else {
                            $doodle[$row][$column] = false;
                        }
                    }
                }
                
                // write back changes
                $fh = fopen($dfile, 'w');
                fwrite($fh, serialize($doodle));
                fclose($fh);
                
            } else if (($submit = $_REQUEST[$dID.'-delete']) && $write_access) {
                
                // user has just deleted a row -> update results
                $row = trim($submit);
                $change_row = "";
                
                if (!empty($row)){
                    unset($doodle[$row]);
                }
                
                // write back changes
                $fh = fopen($dfile, 'w');
                fwrite($fh, serialize($doodle));
                fclose($fh);
            
            } else if (($submit = $_REQUEST[$dID.'-change']) && $write_access) {
                
                // user want to change a row
                $change_row = trim($submit);
            }
            
            // sort rows
            ksort ($doodle);
            
            // start outputing the data
            $renderer->table_open();
            
            if (count($doodle) >= 1) {
                
                $add_delete_row = 1;
                
                if ($write_access) {
                    $colspan = $columns_count + 2;
                } else {
                    $colspan = $columns_count + 1;
                }
                
            } else {
            
                $add_delete_row = 0;
                
                if ($write_access) {
                    $colspan = $columns_count + 1;
                } else {
                    $colspan = $columns_count;
                }
            }
            
            
            // render title if not null
            if ($title) {
                $renderer->tablerow_open();
                $renderer->tableheader_open($colspan);
                $renderer->doc .= $title;
                $renderer->tableheader_close();
                $renderer->tablerow_close();
            }
            
            
            // render column titles
            $renderer->tablerow_open();
            
            if ($write_access || (count($doodle) >= 1)) {
                $renderer->tableheader_open();
                $renderer->doc .= $this->getLang('btable_header');
                $renderer->tableheader_close();
            }
            
            foreach ($columns as $column) {
                $renderer->tableheader_open();
                $renderer->doc .= $renderer->_xmlEntities($column);
                $renderer->tableheader_close();
            }
            
            if ($write_access && (count($doodle) >= 1)) {
                $renderer->tableheader_open();
                $renderer->doc .= $this->getLang('btable_header_del');
                $renderer->tableheader_close();
            }
            
            $renderer->tablerow_close();
            
            
            // display results
            if (is_array($doodle) && count($doodle) >= 1) {
                
                $i = 0;
                foreach($rows as $row) {
                    if (!isset($doodle[$row])) {
                        $selectable_rows[$i] = $row;
                        $i++;
                    }
                }
                
                $renderer->doc .= $this->_doodleResults($dID, $doodle, $columns, $columns_count, $rows_count, $change_row, $write_access, $colspan);
                
            } else {
            
                $selectable_rows = $rows;
                
                if (!$write_access) {
                
                    $renderer->doc .= '<tr>';
                    $renderer->doc .= '  <td class="centeralign" colspan="'.$colspan.'">';
                    $renderer->doc .= '    '.$this->getLang('btable_no_entries');
                    $renderer->doc .= '  </td>';
                    $renderer->doc .= '</tr>';
                }
            }
            
            
            // display input form and export link
            $renderer->doc .= $this->_doodleForm($dID, $columns, $columns_count, $selectable_rows, $change_row, $write_access, $colspan, $add_delete_row);
            
            $renderer->table_close();
            
            
            // close input form
            $renderer->doc .= '</form>';
            
            return true;
        }
        return false;
    }
    
    
    function _doodleResults($dID, $doodle, $columns, $columns_count, $total_rows, $change_row, $allow_changes, $colspan) {
        
        global $ID;
        

        $ret   = '';
        $count = array();
        $rows  = array_keys($doodle);
        
        // render table entrys
        foreach ($rows as $row) {
            
            $ret .= '<tr>';
            
            $ret .= '  <td class="rightalign">';
            if ($allow_changes) {
                $ret .= '<input class="button" '.
                               'type="submit" '.
                               'name="'.$dID.'-change" '.
                               'value="'.$row.'" />';
            } else {
                $ret .= $row;
            }
            $ret .= '  </td>';

            
            if (($row != $change_row) || !$allow_changes) {
            
                foreach ($columns as $column) {
                    
                    if ($doodle[$row][$column]) {
                        
                        $class = 'okay';
                        $title = '<img src="'.DOKU_BASE.'lib/images/success.png" '.
                                      'alt="Okay" '.
                                      'width="16" '.
                                      'height="16" />';
                        $count[$column] += 1;
                        
                    } elseif (!isset($doodle[$row][$column])) {
                    
                        $class = 'centeralign';
                        $title = '&nbsp;';
                        
                    } else {
                        $class = 'notokay';
                        $title = '&nbsp;';
                    }
                    
                    $ret .= '<td class="'.$class.'">'.$title.'</td>';
                }
                
            } else {

                for ($i = 0; $i < $columns_count; $i++) {
                
                    $column = $columns[$i];
                
                    if ($doodle[$row][$column]) {
                        
                        $class = 'centeralign';
                        $value = 'checked="checked"';
                        $count[$column] += 1;
                        
                    } else {
                        $class = 'centeralign';
                        $value = '';
                    }
                    
                    $ret .= '<td class="'.$class.'">';
                    $ret .= '    <input type="checkbox" '.
                                       'name="'.$dID.'-column'.$i.'" '.
                                       'value="1" '.
                                       $value.' />';
                    $ret .= '</td>';
                }
            }

            if ($allow_changes) {
                $ret .= '    <td>';
                $ret .= '        <input type="image" '.
                                       'name="'.$dID.'-delete" '.
                                       'value="'.$row.'" '.
                                       'src="'.DOKU_BASE.'lib/images/del.png" '.
                                       'alt="'.$this->getLang('btable_btn_delete').'" />';
                $ret .= '    </td>';
            }
            $ret .= '</tr>';
        }
        
        if ($this->getConf('btable_show_ratio') == true) {
        
            // render attendance factor
            $ret .= '<tr>';
            $ret .= '  <td>'.$this->getLang('btable_summary').'</td>';
            
            $rows_count = count($rows);
            
            foreach ($columns as $column) {

                $ccount = isset($count[$column]) ? $count[$column] : 0;
                $attendence = $count[$column] / $rows_count;
                $attendance_factor = $this->getConf('btable_ratio') / 100;
                
                if ($attendance_factor < 0 || $attendance_factor > 1) {
                    $attendance_factor = 0.7;
                }
                
                if ($attendence >= $attendance_factor) {
                    $class = 'okay';
                } else {
                    $class = 'notokay';
                }
                
                $ret .= '<td class="'.$class.'">';
                $ret .=    $ccount."/".$rows_count;
                $ret .= '</td>';
            }
            
            if ($allow_changes) {
                $ret .= '<td></td>';
            }
            
            $ret .= '</tr>';
        }
        
        return $ret;
    }
    
    
    function _doodleForm($dID, $columns, $columns_count, $rows, $change_row, $allow_changes, $colspan, $add_delete_row) {
        
        global $ID;
        global $INFO;
        
        
        $rows_count = count($rows);
        
        $max_row_length = 0;
        for ($i = 0; $i < $rows_count; $i++) {
            $length = strlen($rows[$i]);
            if ($length > $max_row_length) {
                $max_row_length = $length;
            }
        }

        if ($allow_changes) {
            if ($rows_count > 0) {
                
                $count = array();
     
                if (empty($change_row)) {
                    $ret .= '  <tr>';
                    
                    // row selection (combobox)
                    $ret .= '    <td class="rightalign">';
                    $ret .= '      <select name="row" size="1" style="width: '.$max_row_length.'em;">';
                    
                    for ($i = 0; $i < $rows_count; $i++) {
                        if ($i == 0) {
                            $ret .= '<option selected="selected">'.$rows[$i].'</option>';
                        } else {
                            $ret .= '<option>'.$rows[$i].'</option>';
                        }
                    }
                    
                    $ret .= '      </select>';
                    $ret .= '    </td>';
                    
                    
                    // render column inputs (checkboxes)
                    for ($i = 0; $i < $columns_count; $i++) {
                        
                        $ret .= '    <td class="centeralign">';
                        $ret .= '      <input type="checkbox" '.
                                             'name="'.$dID.'-column'.$i.'" '.
                                             'value="1" />';
                        $ret .= '    </td>';
                    }
                    if ($add_delete_row) {
                        $ret .= '    <td></td>';
                    }
                    $ret .= '  </tr>';
                }
            }
            
            if (($rows_count > 0) || (!empty($change_row))) {
                
                // render sumbit button
                $ret .= '  <tr>';
                $ret .= '    <td class="centeralign" colspan="'.$colspan.'">';
                
                if (!empty($change_row)) {
                    $ret .= '    <input type="hidden" name="row" value="'.$change_row.'" />';
                }
                
                $ret .= '      <input class="button" '.
                                     'type="submit" '.
                                     'name="'.$dID.'-add" '.
                                     'value="'.$this->getLang('btable_btn_submit').'" />';
                $ret .= '    </td>';
                $ret .= '  </tr>';
            }
        }
        
        if ($this->getConf('btable_show_export') == true) {
            
            // render export link
            $ret .= '  <tr>';
            $ret .= '    <td class="rightalign" colspan="'.$colspan.'">';
            $ret .= '      <a href="'.DOKU_BASE.'/lib/plugins/btable/export.php?id='.$dID.'">';
            $ret .=          $this->getLang('btable_export');
            $ret .= '      </a>';
            $ret .= '    </td>';
            $ret .= '  </tr>';
        }
        
        return $ret;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
