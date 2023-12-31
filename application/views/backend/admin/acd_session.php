<div class="row">
	<div class="col-md-12">
    
    	 
		<ul class="nav nav-tabs bordered">
			<li class="active">
            	<a href="#list" data-toggle="tab"><i class="entypo-menu"></i> 
					<?php echo ('Session List');?>
                    	</a></li>
			<li>
            	<a href="#add" data-toggle="tab"><i class="entypo-plus-circled"></i>
					<?php echo ('Add Session');?>
                    	</a></li>
		</ul>
    	 
        
		<div class="tab-content">
             
            <div class="tab-pane box active" id="list">
				
                <table class="table table-bordered datatable table-hover table-striped" id="table_export">
                	<thead>
                		<tr>
                    		<th><div>#</div></th>
                    		<th><div><?php echo ('Name');?></div></th>
                    		<th><div><?php echo ('Is Open');?></div></th>
                    		<th><div><?php echo ('Start Date');?></div></th>
                            <th><div><?php echo ('End Date');?></div></th>
                    		<th><div><?php echo ('Options');?></div></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php $count = 1;foreach($acdSession as $row):?>
                        <tr>
                            <td><?php echo $count++;?></td>
							<td><?php echo $row['name'];?></td>
							<td><?php echo ($row['is_open'] == '1' ? "Yes" : "No");?></td>
							<td><?php echo date('d/m/Y',strtotime($row['strt_dt']));?></td>
							<td><?php echo date('d/m/Y',strtotime($row['end_dt']));?></td>
                            <td>
                              <a href="#" onclick="showAjaxModal('<?php echo base_url();?>index.php?modal/popup/modal_edit_acd_session/<?php echo $row['id'];?>');">
                                            <i class="entypo-pencil"></i>
                                                <?php echo ('Edit');?>
                                            </a>
                                            &nbsp;  &nbsp;  &nbsp;
                                              <a href="#" onclick="confirm_modal('<?php echo base_url();?>index.php?admin/acd_session/delete/<?php echo $row['id'];?>');">
                                            <i class="entypo-trash"></i>
                                                <?php echo ('Delete');?>
                                            </a>
              
                          
                                    
                                    
        					</td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
			</div>
            
			<div class="tab-pane box" id="add" style="padding: 5px">
                <div class="box-content">
                	<?php echo form_open(base_url() . 'index.php?admin/acd_session/create' , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
                        <div class="padded">
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('Name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="name" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                                </div>
                            </div>
                            
                                <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('From Date');?></label>
                                <div class="col-sm-5">
                                   <input type="text" class="datepicker form-control" name="strt_dt">
                                </div>
                            </div>
                        <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('To Date');?></label>
                                <div class="col-sm-5">
                                   <input type="text" class="datepicker form-control" name="end_dt">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('Is Open');?></label>
                                <div class="col-sm-5">
                                    <select name="is_open" class="form-control selectboxit visible" style="width:100%;">
                                    	<?php 
										 
										?>
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    	
                                        <?php
										 
										?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                              <div class="col-sm-offset-3 col-sm-5">
                                  <button type="submit" class="btn btn-info"><?php echo ('Add Session');?></button>
                              </div>
							</div>
                    </form>                
                </div>                
			</div>
			 
		</div>
	</div>
</div>
                   
<script type="text/javascript">

	jQuery(document).ready(function($)
	{
		

		var datatable = $("#table_export").dataTable();
		
		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
		
</script>