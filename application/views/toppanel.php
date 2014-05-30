<div class="contain-to-gridi sticky">
<nav class="top-bar docs-bar" data-topbar data-options="sticky_on: large">
<?php
?>
<ul class="title-area">
    <li class="menu">
<?php
echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $this->config->item('site_logo') . '" alt="Logo" style="max-height: 40px; background-color: white; margin-top: 2px"/></a>';
?>

    </li>
   <li class="divider"></li>
     <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
    <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
  </ul>
 <section class="top-bar-section">
    <!-- Right Nav Section -->
    <ul class="right">
      <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('federations'); ?></a></li>
      <li class="has-dropdown">
        <a href="#">Right Button Dropdown</a>
        <ul class="dropdown">
          <li><a href="#">First link in dropdown</a></li>
        </ul>
      </li>
      <li class="has-form">

        <?php
            $k = MY_Controller::getLang();
            echo '<div class="button [tiny tiny tiny]">'.strtoupper($langs[''.$k.'']['val']).'</div>';
         ?>

      </li>
 
      <?php
         if($loggedin)
         {
      ?>
      <li class="has-form">
         <a href="<?php echo $base_url;?>auth/logout" class="button alert logoutbutton" id="logout"><?php echo lang('btnlogout');?></a>
      </li>
      <?php
         }
      ?>
    </ul>

    <!-- Left Nav Section -->
    <ul class="left">
      <?php
         if($loggedin)
         {
      ?>
      <li><a href="<?php echo $base_url; ?>federations/manage"><?php echo lang('federations'); ?></a></li>
   <li class="divider"></li>
      <li><a href="<?php echo $base_url; ?>providers/idp_list/show"><?php echo lang('identityproviders'); ?></a></li>
   <li class="divider"></li>
      <li><a href="<?php echo $base_url; ?>providers/sp_list/show"><?php echo lang('serviceproviders'); ?></a></li>
   <li class="divider"></li>
   <li class="has-dropdown"><a href="<?php echo $base_url; ?>"><?php echo lang('general'); ?></a>
     <ul class="dropdown">
        <?php
         if($isAdministrator)
         {
            echo '<li><a href="'.$base_url.'smanage/reports">'.lang('sys_menulink').'</a></li>';
         }
        ?>
        <li><a href="<?php echo $base_url; ?>manage/fedcategory/show"><?php echo lang('rrfedcatslist'); ?></a></li>
        <li><a href="<?php echo $base_url; ?>manage/coc/show"><?php echo lang('entcats_menulink'); ?></a></li>
        <li><a href="<?php echo $base_url; ?>attributes/attributes/show"><?php echo lang('rr_attr_defs'); ?></a></li>
        <?php
          if($isAdministrator)
         {

          echo '<li><a href="'. $base_url.'manage/importer">'. lang('rr_meta_importer').'</a></li>';
        ?>
                                        <li class="has-dropdown"><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users'); ?></a>
                                            <ul class="dropdown">
                                                <li><a href="<?php echo $base_url; ?>manage/users/showlist"><?php echo lang('rr_users_list'); ?></a>
                                                <li><a href="<?php echo $base_url; ?>manage/users/add"><?php echo lang('rr_newuser'); ?></a></li>
                                                <li><a href="<?php echo $base_url; ?>manage/users/remove"><?php echo lang('rr_rmuser'); ?></a></li>
                                            </ul>
                                        </li>
        <?php
          }
        ?>
          
     </ul>

   </li>

      <?php
         }
      ?>
      <li class="has-dropdown">
          <a href="<?php echo $base_url; ?>"><?php echo lang('register'); ?></a>
          <ul class="dropdown">
             <li><a href="<?php echo $base_url; ?>providers/idp_registration"><?php echo lang('identityprovider'); ?></a></li>
             <li><a href="<?php echo $base_url; ?>providers/sp_registration"><?php echo lang('serviceprovider'); ?></a></li>
             <?php
               if($loggedin)
               {
             ?>
             <li><a href="<?php echo $base_url; ?>federations/federation_registration"><?php echo lang('rr_federation'); ?></a></li>
             <?php
               }
             ?>
          </ul>
      </li>
    </ul>
  </section>
</nav>
 
</div>

<div id="toppanel" style="display: none">
  <span id="logo">
  <?php
     echo '<a href="' . $base_url . '"><img src="' . $base_url . 'images/' . $this->config->item('site_logo') . '" alt="Logo"/></a>';
     echo "\n";
  ?>
  </span> 
  <?php
  if ($loggedin)
  {
     echo '<div style="display: inline-block">';
     echo '<span class="mobilehidden">' . lang('urloggedas') . '</span> <b> <a href="'.$base_url.'manage/users/show/'.base64url_encode($this->j_auth->current_user()).'" class="profilelink">'. htmlentities($this->j_auth->current_user()). '</a></b>&nbsp;&nbsp;'  ;
     if(empty(j_auth::$timeOffset))
     {
         echo ' <small>UTC+0</small>';
     }
     elseif(j_auth::$timeOffset > 0)
     {
         echo ' <small>UTC+'.j_auth::$timeOffset/60/60 .'</small>';
     }
     else
     {
        echo ' <small>UTC'.j_auth::$timeOffset/60/60 .'</small>';
     }
     echo '</div>';
     echo '<a href="'.$base_url.'auth/logout" class="logoutbutton" id="logout" style="float: right; margin-left: 10px; ">'.lang('btnlogout').'</a>';
  } 
  else
  {
     echo '<div style="display: inline-block">&nbsp;</div>';
  }
    

  ?>
            <div id="langicons">
                <?php
                  $langs = array(
                    'en' => array('path'=>'english','val'=>'english'),
                    'cs' => array('path'=>'cs','val'=>'čeština'),
                    'es' => array('path'=>'es','val'=>'español'),
                    'fr-ca' => array('path'=>'fr-ca','val'=>'français'),
                    'it' => array('path'=>'it','val'=>'italiano'),
                    'lt' => array('path'=>'lt','val'=>'lietuvos'),
                    'pl' => array('path'=>'pl','val'=>'polski'),
                    'pt' => array('path'=>'pt','val'=>'português'),
                   );
                ?>
                <div id="langchange">
                   <span id="langurl" style="display:none;"><?php echo $base_url.'ajax/changelanguage/';?></span>
                   <label>
                   <select  name="changelanguage">
                     <?php
                       $selset = false;
                       foreach($langs as $key=>$value)
                       {
                          if($key === MY_Controller::getLang())
                          {
                              echo '<option value="'.$value['path'].'" selected="selected">'.strtoupper($key).'</option>';
                          }
                          else
                          {
                              echo '<option value="'.$value['path'].'">('.$key.') '.$value['val'].'</option>';

                          }
                       }
                     ?>
                   </select>
                   </label>
                </div>
            </div>
</div>
        <?php
         if(!$loggedin && empty($showloginform))
         {
        ?>
        <form id="login_link" action="#" method="get">
              <button id="loginlink"  type="button" class="loginbutton"><?php echo lang('toploginbtn'); ?></button>
        </form>
                <?php
         }
                ?>
