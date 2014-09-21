<table class="rjp-count-dashboard">
<thead>
	<tr>
		<th></th>
		<th>count</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td><label>Today:</label></td>
		<td><?php echo RJ_PAGECOUNT::get_total_by_date();?></td>
	</tr>
	<tr>
		<td><label>Total Views:</label></td>
		<td><?php echo RJ_PAGECOUNT::get_total();?></td>
	</tr>
</tbody>
</table>
<?php $top=$rj_page_count->get_top();
if($top):
?>
<table>
<thead>
<tr>
	<th>ID</th>
	<th>count</th>
	<th>slug/post_name</th>
</tr>
</thead>
<tbody>
<?php foreach($top as $i):?>
<?php $rp=get_post($i->post_id);?>
<tr>
	<td><?php echo $rp->ID;?></td>
	<td style="text-align:center"><?php echo $i->count?></td>
	<td><?php echo $rp->post_name?></td>
</tr>
<?php endforeach;?>
</tbody>
</table>
<?php endif;//top?>
