<?
/*
Plugin Name: DPKeys
Version: 0.02
Plugin URI: https://github.com/Maxime2/DPKeys
Author: Maxim Zakharov
Author URI: http://www.maxime.net.ru/
Description: DataparkSearch Keywords Plugin, based on WPKeys plugin (http://www.wpkeys.com)
*/ 

define(DPDATABASE,"dp_keys");
define(DPURLPREFIX,"http://inet-sochi.ru/cgi-bin/search.cgi?tmplt=janus.htm.ru&amp;sp=1&amp;sy=0&amp;s=IRPD&amp;q=");
define('DPKEYS_META', '_dpkeywords');
define('DPKEYWORDS_QUERYVAR', 'dpkeys');
define('DPKEYS_REWRITERULES', '1');
  
add_action('init','dpkeys_init');

function dpkeys_init() {
    dpkeys_create_table();
    
    if(isset($_REQUEST['dpkey_enable'])) {
	   dp_key_enable();
    } elseif (isset($_REQUEST['dpkey_disable'])) {
	   dp_key_disable();
    } elseif (isset($_REQUEST['dpkeys_keywords'])) {
	   dp_key_add($_REQUEST['dpkeys_keywords']);
    } elseif (isset($_REQUEST['dpkeys_from_post'])) {
	   dp_key_add($_REQUEST['dpkeys_from_post']);
    } elseif (isset($_REQUEST['dpkeys_permalink_structure'])) {
	   wpupdate_permalink($_REQUEST['dpkeys_permalink_structure']);
    }    
}

function dpkeys_create_table() {
    global $wpdb;
    global $wp_rewrite;
  
    if (isset($wp_rewrite) && $wp_rewrite->using_permalinks()) {
        define('DPKEYS_REWRITEON', '1');
        define('DPKEYS_LINKBASE', $wp_rewrite->root);
    } else {
        define('DPKEYS_REWRITEON', '0');
        define('DPKEYS_LINKBASE', '');
    }
    
    $query = "SHOW TABLES LIKE '".DPDATABASE."'";
    if(!$result = $wpdb->get_var($query)) {
        $createT = "CREATE TABLE `".DPDATABASE."` (
            `keyword` varchar(255) PRIMARY KEY NOT NULL
        )";
        $wpdb->query($createT);
        add_option("dpkey_status", 1);
        add_option("dpkey_permalink", "dpkeys");
    }
}

function dpkeys_add_pages() {
    add_options_page('DPKeys Plugin', 'DPKeys Options', 8, str_replace("\\","/",__FILE__), 'dpkeys_options_page');
}

function dpkeys_options_page() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo "<h2>DPKeys Plugin Manager</h2>";
    echo '<a name="main"></a><fieldset class="options"><legend>Main options</legend>';

    if(get_option("dpkey_status")==1){
	    echo '<form name="dpkeys" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
	    echo "<strong>DPKey is Enabled</strong>";
	    echo '<input type="hidden" name="dpkey_disable" />';
        echo '<div class="submit"><input type="submit"value="Disable it" /></div>';
	} else { 
        echo '<form name="dpkeys" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
        echo "<strong><font color=red>DPKey is Disabled</font></strong>";
	    echo '<input type="hidden" name="dpkey_enable" />';
	    echo '<div class="submit"><input type="submit" value="Enable it" /></div>';
	}

    echo "</form>";
    echo "</fieldset>";
    
    echo '<a name="keywords"></a><fieldset class="keywords"><h3>Keyword Database</h3>';
    echo "Add or delete keywords there. Separate keywords with line breaks";
    echo '<form name="dpkey_key" action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
    echo '<textarea name="dpkeys_keywords" cols="45" rows="6" style="width: 70%; font-size: 12px;" class="code">';    
    
    $keywords = $wpdb->get_col("SELECT * FROM ".DPDATABASE);
    if($keywords) echo implode("\n",$keywords);
                         
    echo '</textarea> ';
	echo '<div class="submit"><input type="submit" name="dpkey_key_add" value="Save Keywords" /></div>';
	
    echo "</form>\n";          
    echo "</fieldset>";
        
    $dpperm=get_option("dpkey_permalink");
    if(!get_option("permalink_structure")) {
        echo '<a name="permalink"></a><fieldset class="keywords"><h3>Permalink Structure</h3>';
        echo '<font color=red>You need specify some at Options -> Permalinks to edit this field</font>';
        echo '<form action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
        echo '<input name="dpkeys_permalink_structure" disabled type="text" class="code" style="width: 60%;" value="'.$dpperm.'" size="50" />';
        echo '<div class="submit"><input type="submit" value="Save Permalink Structure" /></div>';  
        
        echo "</form>\n";          
        echo "</fieldset>";   
    } else {
        echo '<a name="permalink"></a><fieldset class="keywords"><h3>Permalink Structure</h3>';
        echo '<form action="'. $_SERVER["REQUEST_URI"] . '" method="post">';
        echo '<input name="dpkeys_permalink_structure" type="text" class="code" style="width: 60%;" value="'.$dpperm.'" size="50" />';
	    echo '<div class="submit"><input type="submit" value="Save Permalink Structure" /></div>';  
        echo "</form>\n";          
        echo "</fieldset>";
    }

    echo "</div>";
}

function dp_key_enable() {
    update_option("dpkey_status", 1);
}

function dp_key_disable() {
    update_option("dpkey_status", 0);
}

function dp_key_add($tofunc) {
    global $wpdb;
    if(isset($_REQUEST['dpkeys_keywords'])) {
        $query="TRUNCATE `".DPDATABASE."`";
        $wpdb->query($query);
        
        $query="delete from $wpdb->postmeta where meta_key='".DPKEYS_META."'";
        $wpdb->query($query);
    }
    
    $array=explode("\n",$tofunc);
    $keywords_array=array();
    foreach($array as $arra) {
        $trimarra = dpto_lower(trim($arra));
        if(!$trimarra) continue;
        $keywords_array[]= "('$trimarra')";
    }
    
    if(@count($keywords_array)) {
        $query="INSERT IGNORE INTO ".DPDATABASE." (keyword) VALUES ".implode(",", $keywords_array);
        $wpdb->query($query);
        $mys=$wpdb->get_col("SELECT ID FROM wp_posts WHERE post_status = 'publish'");
        foreach($mys as $postid) {
            dpkeys_save($postid);
        }
    } 
}

function dpkeys_from_post() {
    echo '<p><label for="dpkeys"> <a href="http://www.wpkeys.com/" title="Help on WPKeys"><strong><font color="red">43n39e.ru Keys</font></strong> Add Keywords</a>:</label> (Separate multiple keywords with line breaks.)<br />';
    echo '<textarea name="dpkeys_from_post" cols="45" rows="3" style="width: 70%; font-size: 12px;" class="code">';    
    echo '</textarea></p>';

}

function dpto_lower($str) {
    if(function_exists('mb_convert_case')) return mb_convert_case($str, MB_CASE_LOWER,"utf-8");
    return strtolower($str);
}

function dpconvert_keywords($text) {
    global $id, $wp_disabled;
    $offset= 0;
   
    if($wp_disabled) return $text;   
    
    $len = strlen($text); //--- 
    $lowertext = dpto_lower($text);
    
 	if(get_option("dpkey_status")==1) {
        if($keywords=get_post_meta($id, DPKEYS_META, true)) {
            $kk = explode("\n",$keywords);
            $permlink = DPURLPREFIX;       
            $arraycount = count($kk);
            
            for($i=0; $i<$arraycount; $i=$i+2) {
                $fraze = trim($kk[$i]);
                $pos = $kk[$i+1] + $offset;
                if($pos > $len) continue;
                
                if(($pos = strpos($lowertext,$fraze,$pos-$offset)) === false) continue; //--- to avoid repR 
                $pos += $offset;
                
                
                $flen = strlen($fraze);
                //$text .= " pos = $pos\n";
                $start = substr($text,0,$pos);
                $stop = substr($text, $pos + $flen);
                $samtext=substr($text,$pos, $flen);
		$pos1 = strpos($stop,'</a>',0);
		$pos2 = strpos($stop,'<a ',0);
		if ( (($pos2 === false) && ($pos1 === false)) || (($pos1 != false) && ($pos2 != false) && ($pos1 > $pos2)) ) { 
                	if((is_dpkeys_keyword() != $fraze)) {
	                    $link='<a rel="nofollow" href="'.$permlink.$fraze.'" title="search 43n39e.ru on topic '.$fraze.'">'.$samtext.'</a>';
        	        } else {
                	    $link="<b>$samtext</b>";
	                } 
		} else {
			$link = $samtext;
		}
                
                $text=$start.$link.$stop;
                $offset += strlen($link) - $flen;
            }
        }
    }
    return $text;
}

function wpupdate_permalink($someurl){
    global $wpdb;
    $someurl = trim($someurl);
    update_option("dpkey_permalink", $someurl);
}

function wpadd_mod_rules($rules){
	return $rules;
}

function dpkeys_mod_revrite($rewrite) {
	global $wp_rewrite;
	$perm=get_option("dpkey_permalink");
	$keytag_token = '%'.$perm.'%';
	$wp_rewrite->add_rewrite_tag($keytag_token, '(.+)', 'dpkeys=');
	$keywords_structure = $wp_rewrite->root . "$perm/$keytag_token";
	$keywords_rewrite = $wp_rewrite->generate_rewrite_rules($keywords_structure);	
	return ( $rewrite + $keywords_rewrite );
}

function dpkeys_keywords_parseQuery(){
	if (is_dpkeys_keyword()) {
		global $wp_query;
		$wp_query->is_single = false;
		$wp_query->is_page = false;
		$wp_query->is_archive = false;
		$wp_query->is_search = false;
		$wp_query->is_home = false;
		
		add_filter('posts_where', 'dpkeys_postsWhere');
		add_filter('posts_join', 'dpkeys_postsJoin');
		add_action('template_redirect', 'dpkeys_includeTemplate');
	}
}

function dpkeys_postsWhere($where) {
    global $wp_version;
    $keyword = is_dpkeys_keyword();
    $where .= " AND dpkeys_meta.meta_key = '" . DPKEYS_META . "' ";
	$where .= " AND dpkeys_meta.meta_value LIKE '%" . $keyword . "%' ";
    $where = str_replace(' AND (post_status = "publish"', ' AND ((post_status = \'static\' OR post_status = \'publish\')', $where);
	return ($where);
}


function dpkeys_includeTemplate() {
    if (is_dpkeys_keyword()) {
	   $template = get_category_template();
	   if($template) load_template($template);
    }
}

function dpkeys_postsJoin($join) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->postmeta AS dpkeys_meta ON ($wpdb->posts.ID = dpkeys_meta.post_id) ";
	return ($join);
}

function is_dpkeys_keyword() {
    global $wp_version;
    $keyword = ( isset($wp_version) && ($wp_version >= 2.0) ) ? 
                get_query_var(DPKEYWORDS_QUERYVAR) : 
                $GLOBALS[DPKEYWORDS_QUERYVAR];
	if (!is_null($keyword) && ($keyword != '')){
		return $keyword;
	} else return false;
}

function dpkeys_addQueryVar($dpvar_array){
    $dpvar_array[] = DPKEYWORDS_QUERYVAR;
    return($dpvar_array);    
}

function dpkeys_title($blogname,$name) {
    if(($keyword=is_dpkeys_keyword()) && $name=='name') $blogname=$keyword.' at '.$blogname;
    return $blogname;
}

function dpkeys_save($id) {
    global $wpdb, $wp_disabled;
    $post = get_post($id);
    $posttext = strtolower($post->post_content);
    
    $wp_disabled = true;
    $posttext = apply_filters('the_content', $posttext);
		
    $posttext = str_replace(']]>', ']]&gt;', $posttext);
    $posttext = dpoff_tags($posttext);
   
    $posttext = dpto_lower($posttext);
    
    $thispostkeywords=array();
    
    $keywords = $wpdb->get_col("SELECT * FROM ".DPDATABASE);
    $postlen=strlen($posttext);
    if($keywords) {
//        foreach($keywords as $key) {
//        	$key = trim($key);
//            $keyend=0;
//            while(($poz=strpos($posttext, strtolower($key), $keyend))!==false) {
//                $matched = substr($posttext,$poz,strlen($key));
//            	$keyend = $poz + strlen($key);
//            	
//            	
//                if( ( ($poz==0) || !ctype_alnum($posttext[$poz-1])) && ( ($keyend==$postlen) || !ctype_alnum($posttext[$keyend]))  ) {
//                    $thispostkeywords[]=array($key, $poz);
//                    break;
//                }
//            }
//        }
        
        
//        $posttext = iconv('CP1251','UTF-8',$posttext);
//        foreach($keywords as $key) {
//            $result = preg_replace('~(\A|\s)('.$key.')(\s|\z)~i',"\\1\x01\\3",$posttext);
//            $len=strlen($result);
//            for($i=0;$i<$len;$i++) {
//                if (ord($result{$i}) >= 128 and ord($result{$i+1}) >= 128) {$result{$i} = ''; $result{$i+1} = '0';}
//            }
//            $result = str_replace("\x00",'',$result);
//            if(($poz = strpos($result,"\x01")) !== false)  $thispostkeywords[]=array($key, $poz);

/*
foreach ($keywords as $key)
  if (preg_match('/(?<![\w\x80-\xFF])'.preg_quote($key,'/').'(?![\w\x80-\xFF])/',$posttext,
	$regs,PREG_OFFSET_CAPTURE))
    $thispostkeywords[]=array($key,$regs[0][1]);
*/
//$tmp=implode('|',array_map('preg_quote',$keywords,array_fill(0,sizeof($keywords),'/')));

//mb_internal_encoding('UTF-8');
//mb_regex_encoding('UTF-8');

$thispostkeywords = array();
foreach($keywords as $key) {
    if (preg_match('/(?<![\\w\x80-\xFF])'.preg_quote($key,'/').'(?![\\w\x80-\xFF])/',$posttext,
	$regs,PREG_OFFSET_CAPTURE)) $thispostkeywords[]=array($key,$regs[0][1]);
}


//if (preg_match_all("/(?<![\\w\x80-\xFF])($tmp)(?![\\w\x80-\xFF])/i",$posttext,$regs,PREG_OFFSET_CAPTURE))
//        $thispostkeywords=$regs[0];
    
        
        $skoko=count($thispostkeywords);
        for ($i=0;$i<$skoko;$i++) {
            if(!$thispostkeywords[$i][0]) continue; 
            for ($j=0; $j<$skoko; $j++) {
                if ( ($i==$j) || (!$thispostkeywords[$j][0]) ) continue;
                if ( strpos($thispostkeywords[$i][0], $thispostkeywords[$j][0])!==false ) {
                    $thispostkeywords[$j][0]='';
                    continue;
                }
            }
        }
        
        usort($thispostkeywords, "dpcmp");
        
        $nash=array();
        
        foreach ($thispostkeywords as $kkk){
            if($kkk[0]!='') {
            	$nash[]=$kkk[0]; 
            	$nash[]=$kkk[1];   
            }
        }
        
        
        $tobase=addslashes(implode("\n",$nash));
        if(!get_post_meta($id, DPKEYS_META, true)) {
            if($tobase) $wpdb->query("INSERT IGNORE INTO $wpdb->postmeta (post_id,meta_key,meta_value) VALUES ('$id', '".DPKEYS_META."' , '$tobase')");
        } else {
            if($tobase) {
                $wpdb->query("UPDATE $wpdb->postmeta SET meta_value='$tobase' where meta_key='".DPKEYS_META."' and post_id=$id");
            } else {
                $wpdb->query("delete from $wpdb->postmeta where meta_key='".DPKEYS_META."' and post_id=$id");
            }
            
      }
   }
}

function dpcmp($a, $b) {
   if ($a[1] == $b[1]) return 0;
   return ($a[1] < $b[1]) ? -1 : 1;
}

function dpkeys_post($post) {    
    if( ($key=is_dpkeys_keyword())!==false ) {
        $addto="<br><br>This page is generated by <a href='http://www.dataparksearch.org/Other'>Dpkeys</a> plugin";
        $post[count($post)-1]->post_content.=$addto;
    }
    
    return $post;
}

function dpoff_tags($posttext) {
	$posttext=strtolower($posttext);
	$offset=0;
	while ( ($start_link=strpos ( $posttext, "<", $offset))!==false ) {
		if ( $posttext[$start_link + 1]=='a') { 
			$end_link=strpos($posttext, "</a>", $start_link);
			$end_link += 4;
		} else {
		    $end_link=strpos($posttext, ">", $start_link);
            $end_link += 1;
		}
        
		$len=$end_link-$start_link;
        $new_link = str_repeat('*',$len);
        $posttext=substr($posttext, 0, $start_link).$new_link.substr($posttext, $end_link);
		$offset=$end_link;
	}
	return $posttext;
}


add_action('admin_menu', 'dpkeys_add_pages');
add_action('edit_form_advanced','dpkeys_from_post');
add_action('edit_form','dpkeys_from_post');
add_filter('the_content', 'dpconvert_keywords', 100000001); //---- really last filter, should be after WPKeys ---
add_filter('rewrite_rules_array', 'dpkeys_mod_revrite');
add_filter('bloginfo', 'dpkeys_title',10,2);
add_action('parse_query', 'dpkeys_keywords_parseQuery');
add_filter('query_vars', 'dpkeys_addQueryVar');
add_action('save_post', 'dpkeys_save');
add_filter('the_posts', 'dpkeys_post');

?>
