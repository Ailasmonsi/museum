<статья>
   <?php 
      $img = get_sub_field('team_member_profile_pic');
      $img = $img['размеры']['эскиз']; 
   ?>
   класс<div ="acf-inner">
      <?php if ( $img ) { ?>
      src<img ="<?php echo $img; ?>" alt="" />
      <?php }?>
      класс<div ="details">
         <h3><?php the_sub_field('team_member_name'); ?></h3>
         класс<p ="entry-meta">
            <?php the_sub_field('team_member_position'); ?>
         </p>
         <?php the_sub_field('team_member_biography'); ?> 
      </div>
   </div>
</article>