<div id="subtitle"><h3><a href="<?php echo base_url().'providers/detail/show/'.$entdetail['id'];?>"><?php echo $entdetail['displayname'] ; ?></a></h3><h4><?php echo $entdetail['entityid']; ?></h4> </div>
<?php
if(!empty($success_message))
echo '<div>'.$success_message.'</div>';
redirect(base_url().'providers/detail/show/'.$entdetail['id'], 'refresh'); 
?>
