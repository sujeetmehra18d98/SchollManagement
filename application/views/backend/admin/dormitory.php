<div class="row">
	<div class="col-md-12">
     
		<ul class="nav nav-tabs bordered">
			<li class="active">
            	<a href="#list" data-toggle="tab"><i class="entypo-menu"></i> 
					<?php echo ('Dormitory List');?>
                    	</a></li>
			<li>
            	<a href="#add" data-toggle="tab"><i class="entypo-plus-circled"></i>
					<?php echo ('Add Dormitory');?>
                    	</a></li>
		</ul>
    	 
        
	
		<div class="tab-content">
           
            <div class="tab-pane box <?php if(!isset($edit_data))echo 'active';?>" id="list">
                <table cellpadding="0" cellspacing="0" border="0" class="table table-bordered table-hover table-striped datatable" id="table_export">
                	<thead>
                		<tr>
                    		<th><div><?php echo ('Dormitory Name');?></div></th>
                    		<th><div><?php echo ('Number of Room');?></div></th>
                    		<th><div><?php echo ('Description');?></div></th>
                    		<th><div><?php echo ('Options');?></div></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php $count = 1;foreach($dormitories as $row):?>
                        <tr>
							<td><?php echo $row['name'];?></td>
							<td><?php echo $row['number_of_room'];?></td>
							<td><?php echo $row['description'];?></td>
							<td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-default pull-right" role="menu">
                                    
                                    
                                    <li>
                                        <a href="#" onclick="showAjaxModal('<?php echo base_url();?>index.php?modal/popup/modal_edit_dormitory/<?php echo $row['dormitory_id'];?>');">
                                            <i class="entypo-pencil"></i>
                                                <?php echo ('Edit');?>
                                            </a>
                                                    </li>
                                    <li class="divider"></li>
                                  
                                    <li>
                                        <a href="#" onclick="confirm_modal('<?php echo base_url();?>index.php?admin/dormitory/delete/<?php echo $row['dormitory_id'];?>');">
                                            <i class="entypo-trash"></i>
                                                <?php echo ('Delete');?>
                                            </a>
                                                    </li>
                                </ul>
                            </div>
        					</td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
			</div>
            
			<div class="tab-pane box" id="add" style="padding: 5px">
                <div class="box-content">
                	<?php echo form_open(base_url() . 'index.php?admin/dormitory/create' , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('Dormitory Name');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="name"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('Number of Room');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="number_of_room"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"><?php echo ('Description');?></label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" name="description"/>
                                </div>
                            </div>
                        <div class="form-group">
                              <div class="col-sm-offset-3 col-sm-5">
                                  <button type="submit" class="btn btn-info"><?php echo ('Add Dormitory');?></button>
                              </div>
							</div>
                    </form>                
                </div>                
			</div>
			 
            
		</div>
	</div>
</div>