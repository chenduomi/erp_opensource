<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\models\SysCarrierParam;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\apihelpers\ApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//上传js
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['consignee_country_code'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['consignee_country_code']),true)).";" , \yii\web\View::POS_READY);
}
//国家搜索选择js
$this->registerJs("OrderCommon.NationList=".json_encode($countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode($country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
//常用搜索js
$this->registerJs("OrderCommon.customCondition=".json_encode($custom_condition).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);
//自定义标签三个文件
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode(OrderTagHelper::getTagColorMapping()).";" , \yii\web\View::POS_READY);
//订单批量操作公用js文件
$this->registerJsFile($baseUrl."js/project/order/orderActionPublic.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carrierQtip.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("carrierQtip.initCarrierQtip('".json_encode(@$carrierQtips)."');" , \yii\web\View::POS_READY);

//$this->registerJsFile($baseUrl."js/project/delivery/order/listplanceanorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/carrieroperate/lbalionlinedelivery.js", ['depends' => ['yii\web\JqueryAsset']]);

$tmpOdtimetype = Odorder::$timetype;
$tmpCustomsort = Odorder::$customsort;
if($_REQUEST['carrier_step'] == 'UPLOAD'){
	unset($keys['tracknum']);
	unset($search['no_print_carrier']);
	unset($search['print_carrier']);
	unset($tmpOdtimetype['printtime']);
	unset($tmpOdtimetype['shiptime']);
	unset($tmpCustomsort['printtime']);
	unset($tmpCustomsort['shiptime']);
}

$user=\Yii::$app->user->identity;
$puid = $user->getParentUid();
?>
<style>
.modal-content{
		border-color:#797979;
	}
.modal-header .modal-title {
	background:#364655;color: white;
}
.btn_tag_qtip a {
  margin-right: 5px;
}
.div_select_tag>.input-group , .div_new_tag>.input-group{
  float: left;
  width: 32%;
  vertical-align: middle;
  padding-right: 10px;
  padding-left: 10px;
  margin-bottom: 10px;
}

.div_select_tag{
	display: inline-block;
	border-bottom: 1px dotted #d4dde4;
	margin-bottom: 10px;
}
.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}
.div_add_tag{
	width: 600px;
}
.table td, .table th {
    text-align: center;
	word-break:break-all;
}	

.order-param-group , .prod-param-group{
	width: 280px;
	float: left;
	text-align: right;
	display: block;
	margin-right: 10px;
}
</style>
<?php echo $this->render('_leftmenu');?>
<div class="content-wrapper" >
<div style="margin-top:10px;">

<!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
	<div style="margin-top:15px">
		<!-- 搜索区域 -->
		<form class="form-inline" id="searchForm" name="form1" action="" method="post">
		<?=Html::hiddenInput('showsearch',$showsearch,['id'=>'showsearch']);?>
		<?=Html::hiddenInput('carrier_type',$query_condition['carrier_type'],['id'=>'carrier_type']);?>
		<div style="margin:0px 0px 0px 0px">
		<!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
		<?=Html::dropDownList('selleruserid',isset($query_condition['selleruserid'])?$query_condition['selleruserid']:'',$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'平台 & 店铺'])?>
		<!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
			<div class="input-group iv-input">
				<?=Html::dropDownList('keys',isset($query_condition['keys'])?$query_condition['keys']:'',$keys,['class'=>'iv-input','style'=>'width:100px;'])?>
		      	<?=Html::textInput('searchval',isset($query_condition['searchval'])?$query_condition['searchval']:'',['class'=>'iv-input','id'=>'num'])?>
		      	
		    </div>
		    <!----------------------------------------------------------- 模糊搜索 ----------------------------------------------------------->
		    <?php // Html::checkbox('fuzzy',isset($query_condition['fuzzy'])?$query_condition['fuzzy']:'',['label'=>TranslateHelper::t('模糊搜索')])?>
		    
		    <!----------------------------------------------------------- 高级搜索 ----------------------------------------------------------->
	    	<a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();"><?php if($showsearch!='1'){?>高级搜索<span class="glyphicon glyphicon-menu-down"></span><?php }else {?>收起<span class="glyphicon glyphicon-menu-up"></span><?php }?></a>
		    
		    <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
		    <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		    
		    <div class="pull-right" style="height: 40px;">
	    	<?php // Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
	    		 
	    	<!----------------------------------------------------------- 常用筛选 ----------------------------------------------------------->
	    	<?php echo Html::dropDownList('sel_custom_condition',isset($query_condition['sel_custom_condition'])?$query_condition['sel_custom_condition']:'',$sel_custom_condition,['prompt'=>'常用筛选','class'=>'iv-input'])?>
	    	<?=Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
	    	</div>
	    	
	    	<!----------------------------------------------------------- 保持高级搜索展开 ----------------------------------------------------------->
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<div class="input-group"  name="div-select-nation"  data-role-id="0"  style='margin:0px'>
			<?=Html::textInput('consignee_country_code',isset($query_condition['consignee_country_code'])?$query_condition['consignee_country_code']:'',['class'=>'iv-input','placeholder'=>'请选择国家','style'=>'width:200px;margin:0px'])?>
			</div>
			<?php echo (count($warehouseIdNameMap) > 1) ? Html::dropDownList('default_warehouse_id',isset($query_condition['default_warehouse_id'])?$query_condition['default_warehouse_id']:'',$warehouseIdNameMap,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:200px;margin:0px']) : '';?>
			<?=Html::dropDownList('default_carrier_code',isset($query_condition['default_carrier_code'])?$query_condition['default_carrier_code']:'',$allcarriers,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('default_shipping_method_code',isset($query_condition['default_shipping_method_code'])?$query_condition['default_shipping_method_code']:'',$allshippingservices,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
			
			</div>
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<?php //echo Html::dropDownList('fuhe',isset($query_condition['fuhe'])?$query_condition['fuhe']:'',$search,['class'=>'iv-input','prompt'=>'快速筛选','id'=>'fuhe','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('reorder_type',isset($query_condition['reorder_type'])?$query_condition['reorder_type']:'',Odorder::$reorderType,['prompt'=>'重新发货类型','class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 <?=Html::dropDownList('timetype',isset($query_condition['timetype'])?$query_condition['timetype']:'',$tmpOdtimetype,['class'=>'iv-input'])?>
        	<?=Html::input('date','date_from',isset($query_condition['date_from'])?$query_condition['date_from']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','date_to',isset($query_condition['date_to'])?$query_condition['date_to']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			<strong style="font-weight: bold;font-size:12px;">排序：</strong>
			<?=Html::dropDownList('customsort',isset($query_condition['customsort'])?$query_condition['customsort']:'',$tmpCustomsort,['class'=>'iv-input','style'=>'width:100px;margin:0px'])?>
			<?=Html::checkbox('ordersorttype',isset($query_condition['ordersorttype'])?$query_condition['ordersorttype']:'',['label'=>TranslateHelper::t('升序'),'value'=>'asc'])?>
			</div>
			<!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,isset($query_condition[$tag_code])?$query_condition[$tag_code]:'',['label'=>TranslateHelper::t($label),'onclick'=>'label_check_radio(this)']);
			}
			//echo Html::checkbox('is_reverse',isset($query_condition['is_reverse'])?$query_condition['is_reverse']:'',['label'=>TranslateHelper::t('取反')]);
			?>
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<?php if(count($all_tag_list) > 0){ ?>
			<div style="margin:20px 0px 0px 0px">
			<div class="pull-left">
			<strong style="font-weight: bold;font-size:12px;">自定义标签：</strong>
			</div>
			<div class="pull-left" style="height: 40px;">
			<?=Html::checkboxlist('custom_tag',isset($query_condition['custom_tag'])?$query_condition['custom_tag']:'',$all_tag_list);?>
			</div>
			</div>
			<?php } ?>
			</div> 	
				
	    </div>
		</form>
	</div>
<!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
<div style="height:20px;clear: both;"><hr></div>
<div>
<!-- --------------------------------------------批量操作项begin--------------------------------------------------------------- -->
<div class="pull-left" style="height: 40px;">
<!-- 接口对接 -->
<?php if($_REQUEST['carrier_type'] == 1){?>
	<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
		echo Html::button(TranslateHelper::t('上传且交运'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'uploadAndDispatch_btn','style'=>'margin-right:3px;']);
		echo Html::button(TranslateHelper::t('修改报关信息'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'editCustomsInfo_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口上传');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
		echo Html::button(TranslateHelper::t('交运'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'dodispatch_btn','style'=>'margin-right:3px;']);
		echo Html::button(TranslateHelper::t('重新上传'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'moveToUpload_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口交运');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERYED'){
		echo Html::button(TranslateHelper::t('确认发货完成'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'setFinished_btn','style'=>'margin-right:3px;']);
		echo Html::button(TranslateHelper::t('获取跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'getTrackNo_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口已交运');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
		echo Html::button(TranslateHelper::t('获取跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'getTrackNo_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口已完成');
		?>
	<?php }?>
<!-- excel对接 -->
<?php }elseif($_REQUEST['carrier_type'] == 2){?>
	<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel未导出');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel已导出');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel已完成');
		?>
	<?php }?>
	<?= html::dropDownList('excelCarriers',@$_REQUEST['default_carrier_code'],$allcarriers,['class'=>'iv-input','prompt'=>'物流商','onchange'=>'changeExcelCarrier($(this).val())'])?>
	<input type="button" class="iv-btn btn-important" onclick="exportExcel('<?= @$_REQUEST['default_carrier_code']?>')" value="导出" />&nbsp;
<!-- 无数据对接 -->
<?php }elseif($_REQUEST['carrier_type'] == 3){?>
		<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
			echo Html::button(TranslateHelper::t('分配跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'setTrackNum_btn','style'=>'margin-right:3px;']);
			
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','未分配');
			?>
		<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','已分配');
			?>
		<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','已完成');
			?>
		<?php }?>
<?php }?>
<!-- </div> -->
<!-- <div class="pull-left" style="height: 40px;"> -->
		<?php if (isset($doAction['moveToPacking'])){unset($doAction['moveToPacking']); }?>
		<?php if(empty($doAction)) $doAction = array(); ?>
		<?=Html::dropDownList('do','',$doAction,['onchange'=>"doactionnew(this);",'class'=>'iv-input do','style'=>'width:190px;margin:0px']);?> 
		<?php echo (count($excelmodels) > 0) ? Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'prompt'=>'自定义格式Excel导出','class'=>'iv-input do','style'=>'width:150px;margin:0px']) : '' ;?> 
		<?=Html::dropDownList('do','',OdOrder::$exportOperationList,['onchange'=>"OrderCommon.DeliveryBatchOperation(this);",'prompt'=>'小老板固定格式Excel导出','class'=>'iv-input do','style'=>'width:170px;margin:0px']);?> 
		
		</div>
		<div class="pull-right" style="height: 40px;">
		<?php echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox()"]);?>
		</div>
		
<?php if(isset($_REQUEST['carrier_step'])){
	if($_REQUEST['carrier_step'] != 'UPLOAD'){
		?>
<div style="height:1px;clear: both;"></div>
<div class="pull-left" style="height: 40px;">
		
<?= Html::dropdownlist('shipmethod',isset($query_condition['default_shipping_method_code'])?$query_condition['default_shipping_method_code']:'',$shippingServices,['prompt'=>'请先选择运输服务再打印物流面单','class'=>'iv-input','style'=>'width:380px;'])?>
<input type="button" class="iv-btn <?= (isset($printMode['is_api_print']) && !empty($printMode['is_api_print']))?'btn-important':'disabled'?>" onclick="<?= (isset($printMode['is_api_print']) && !empty($printMode['is_api_print']))?"doprint('api')":''?>" value="API获取标签打印"/>
<input type="button" class="iv-btn <?= (isset($printMode['is_print']) && !empty($printMode['is_print']))?'btn-important':'disabled'?>" onclick="<?= (isset($printMode['is_print']) && !empty($printMode['is_print']))?"doprint('gaofang')":''?>" value="高仿标签打印" />
<input type="button" class="iv-btn <?= (isset($printMode['is_custom_print']) && !empty($printMode['is_custom_print']))?'btn-important':'disabled'?>" onclick="<?= (isset($printMode['is_custom_print']) && !empty($printMode['is_custom_print']))?"doprint('custom')":''?>" value="自定义标签打印" />
<input type="button" class="iv-btn <?= (isset($printMode['is_api_print']) && !empty($printMode['is_api_print']))?'btn-important':'disabled'?>" onclick="<?= (isset($printMode['is_api_print']) && !empty($printMode['is_api_print']))?"doprint('integrationlabel')":''?>" value="一体化标签打印"/>
<span qtipkey="carrier_integration_lable"></span>

</div>
		<?php
	}
} ?>
		
		
<!-- --------------------------------------------批量操作项 end--------------------------------------------------------------- -->
<?php if($_REQUEST['carrier_step'] != 'UPLOAD' ){  //&& $_REQUEST['carrier_type']!=1?>  
<form action="" method="post" target="_self" id="ordersform" name = 'a'>
<?php }?>
	<table class="table table-condensed table-bordered" style="font-size:12px;margin-top:20px;">
		<tr>
		<th width="6%">
		<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox" check-all="e1" />
		</th>
		<th width="6%"><b>小老板单号</b></th>
		<th width="22%"><b>商品SKU</b></th>
		<th width="8%"><b>总价</b></th>
		<th width="10%"><b>下单日期/付款日期</b></th>
		<th width="20%"><b>运输服务</b></th>
		<th width="6%"><b>收件国家</b></th>
		<th width="6%"><b>平台/站点</b></th>
		<th width="6%"><b>平台状态</b></th>
		<th style="min-width: 120px;"><b>操作</b></th>
		</tr>

		<?php 
		$divTagHtml = "";
		?>
		<?php if (count($orders)){foreach ($orders as $order):?>
		<!-- --------------------------------------------订单--------------------------------------------------------------- -->
		<tr style="background-color: #f4f9fc;border:1px solid #d1d1d1;" class="line-<?php echo $order->order_id;?>">
			<td><span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,'<?=$order->order_id?>');"></span><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>" data-check="e1">
			</td>
			<td>
				<?=$order->order_id?><br>
			<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
				<div title="<?=OdOrder::$exceptionstatus[$order->exception_status]?>" class="exception_<?=$order->exception_status?>"></div>
			<?php endif;?>
			<?php if (strlen($order->user_message)>0):?>
				<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
			<?php endif;?>
			<?php 
		            $divTagHtml .= '<div id="div_tag_'.$order['order_id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
		            $TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($order['order_id']);
		            if (!empty($TagStr)){
		            	$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$order['order_id']."' >$TagStr</span>";
		            }
		            echo $TagStr;
		            ?>
			</td>
			<td>
				<?php if (count($order->items)):foreach ($order->items as $item):?>
				<?php if (isset($item->sku)&&strlen($item->sku)):?>
				<?=$item->sku?>&nbsp;<b>X<span <?php if ($item->quantity>1){echo 'class="multiitem"';}?>><?=$item->quantity?></span></b><br>
				<?php endif;?>
				<?php endforeach;endif;?>
			</td>
			<td>
				<?=$order->grand_total?>&nbsp;<?=$order->currency?>
			</td>
			<td>
			<?=$order->order_source_create_time>0?date('y/m/d H:i:s',$order->order_source_create_time):''?><br>
			<?=$order->paid_time>0?date('y/m/d H:i:s',$order->paid_time):''?>
			</td>
			<td style="text-align: left;">
			客选物流：
			<?php 
			if($order->order_source=="aliexpress"){
				if(!empty($order->addi_info)){
					$addi_info_arr = json_decode($order->addi_info, true);
					if(!empty($addi_info_arr)){
						if(isset($addi_info_arr['shipping_service'])){
							if(is_array($addi_info_arr['shipping_service'])){
								echo implode(', ',$addi_info_arr['shipping_service']);
							}
						}
					}
				}
			}else{
				echo $order->order_source_shipping_method;
			}
			?>
			<br>
			运输服务：<?php echo isset($allshippingservices[$order->default_shipping_method_code])?$allshippingservices[$order->default_shipping_method_code]:'';?>
			<?php if ($order->is_print_carrier ==1){echo '<span class="glyphicon glyphicon-print" aria-hidden="true" title="已打印物流面单"></span>';}?>
			</td>
			<td>
				<label title="<?=$order->consignee_country?>"><?=$order->consignee_country_code?></label>
			</td>
			<td>
				<?php echo $order->order_source.(empty($order->order_source_site_id) ? '' : '/'.$order->order_source_site_id);?>
			</td>
			<td>
				<b><?php echo $order->order_source_status;?></b>
			</td>
			<td>
			<?php if ($order->order_source=="aliexpress"){?>
			<a href="<?=Url::to(['/order/aliexpressorder/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="ebay"){?>
			<a href="<?=Url::to(['/order/order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="amazon"){?>
			<a href="<?=Url::to(['/order/amazon-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="cdiscount"){?>
			<a href="<?=Url::to(['/order/cdiscount-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="bonanza"){?>
			<a href="<?=Url::to(['/order/bonanza-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="dhgate"){?>
			<a href="<?=Url::to(['/order/dhgate-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="ensogo"){?>
			<a href="<?=Url::to(['/order/ensogo-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="jumia"){?>
			<a href="<?=Url::to(['/order/jumia-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="lazada"){?>
			<a href="<?=Url::to(['/order/lazada-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="linio"){?>
			<a href="<?=Url::to(['/order/linio-order/edit','orderid'=>$order->order_id])?>"" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="priceminister"){?>
			<a href="<?=Url::to(['/order/priceminister-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="wish"){?>
			<a href="<?=Url::to(['/order/wish-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }?>
			<a href="<?=Url::to(['/order/logshow/list','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-paperclip" title="订单修改日志"></span></a>
			</td>
		</tr>
<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
		<!-- --------------------------------------------商品--------------------------------------------------------------- -->
		<tr class="xiangqing <?=$order->order_id?> line-<?php echo $order->order_id;?>">
			<td style="border:1px solid #d1d1d1;"><img src="<?= (in_array($order->order_source, array('cdiscount','priceminister')) ? eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item->photo_primary, $puid, 1) : $item->photo_primary) ?>" width="60px" height="60px">
			<td colspan="2" style="border:1px solid #d1d1d1;text-align:justify;">
				订单号:<b style="color:#ff9900;"><?=$item->order_source_order_id?></b><br>
				<?php
				if((!empty($order['customer_number'])) || (substr($order['default_carrier_code'], 0, 3) != 'lb_')){
					echo '跟踪号:'.CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code'],$order['default_carrier_code']).'<br>';
				}
				?>
				SKU:<b><?=$item->sku?></b><br>
				<?= (empty($item->product_url) ? $item->product_name : '<a href="'.$item->product_url.'" target="_blank">'.$item->product_name.'</a>') ?>
				<?php
					if($order->order_source == 'aliexpress'){
						if (!empty($item->product_attributes)){
							$tmpProdctAttrbutes = explode(' + ' ,$item->product_attributes );
							if (!empty($tmpProdctAttrbutes)){
								echo '<br/>';
								foreach($tmpProdctAttrbutes as $_tmpAttr){
									echo '<span class="label label-warning">'.$_tmpAttr.'</span>';
								}
							}
						}
					}
				?>
			</td>
			<td  style="border:1px solid #d1d1d1">
				<?=$item->quantity?>
			</td>
			<?php if ($key=='0'):?>
			<td rowspan="<?=count($order->items)?>" style="border:1px solid #d1d1d1;text-align:left;" class="text-nowrap">
			<?php echo $order->default_warehouse_id == -1?"未指定仓库":$warehouseIdNameMap[$order->default_warehouse_id];?>
			<?php if ($order->is_print_distribution ==1){echo '<span class="glyphicon glyphicon-print" aria-hidden="true" title="已打印配货单"></span>';}?>
			</td>
			<td rowspan="<?=count($order->items)?>" style="border:1px solid #d1d1d1;text-align:left;" class="text-nowrap">
				<?php if($order->order_source == 'amazon') { ?>
				<font color="#8b8b8b">amazon店铺名:</font>
				<b><?=@substr($selleruserids[$order->selleruserid], 2, strlen($selleruserids[$order->selleruserid])-2);?></b><br>
				<?php }else{
				?>
				<font color="#8b8b8b">卖家账号:</font>
				<b><?=$order->selleruserid?></b><br>
				<?php } ?>
				<font color="#8b8b8b">买家姓名:</font>
				<b><?=$order->consignee?></b><br>
				<font color="#8b8b8b">买家账号:</font>
				<b><?=$order->source_buyer_user_id?></b><br>
				<font color="#8b8b8b">买家邮箱:</font>
				<b><?=$order->consignee_email?></b>
			</td>
			<td colspan="3"  rowspan="<?=count($order->items)?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;text-align:left;">
				<?php 
				if (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND  , OdOrder::STATUS_SHIPPING])){
					$tmpTimeLeft =  ((!empty($order->fulfill_deadline))?'<span id="timeleft_'.$order->order_id.'" class="fulfill_timeleft" data-order-id="'.$order->order_id.'" data-time="'.($order->fulfill_deadline-time()).'"></span><br>':"");
					echo $tmpTimeLeft;
				}
				?>
				<font color="#8b8b8b">付款备注:</font><br><b class="text-warning"><?=$order->user_message?></b>
			</td>
			<td  rowspan="<?=count($order->items)?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
			<span><font color="red" id="desc-<?php echo $order->order_id;?>"><?=$order->desc?></font></span>
				<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font color="00bb9b">备注</font></a>
			</td>
			<?php endif;?>
		</tr>	
		<?php endforeach;endif;?>
<!-- --------------------------------------------物流参数begin--------------------------------------------------------------- -->
<?php if ($_REQUEST['carrier_step'] == 'UPLOAD' && $_REQUEST['carrier_type']==1){?>
<tr class=" appatch orderInfo line-<?php echo $order->order_id;?> xiangqing <?=$order->order_id?>" id="formline-<?php echo $order->order_id;?>"> 
<td colspan="10" class="row" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1" >
	
		<form class="form-inline">
		<?php 
			echo $orderHtml[$order->order_id];
 		?>
		</form>
</td>
</tr>
<?php }?>
<!-- --------------------------------------------物流参数end--------------------------------------------------------------- -->
<tr style="background-color: #d9d9d9;" class="xiangqing <?=$order->order_id?>">
	<td colspan="10" class="row" id="dataline-<?php echo $order->order_id;?>" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
	<?php if(!empty($order->carrier_error)){?>
		<div class="alert-danger" id="message-<?php echo $order->order_id;?>" role="alert" style="text-align:left;"><?php echo $order->carrier_error;?></div>
	<?php }?>
	</td>
</tr>
<?php endforeach;}?>
</table>
<?php if($_REQUEST['carrier_step'] != 'UPLOAD'){?>
</form>
<?php }?>
<!-- --------------------------------操作成功显示区域-------------------------------------------- -->
<!-- --------------------------------分页-------------------------------------------- -->
<?php if($pagination):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%; text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
	</div>
<?php endif;?>
<!-- --------------------------------分页-------------------------------------------- -->		
</div>
</div>
<?=$divTagHtml?>
</div>

<form name="order-related-additional-operation-form" style="display:none;" method="post" target="_self">
<input type="hidden" name="order_id" />
<input type="hidden" name="js_submit" value='js_submit' />
</form>

<script>
//保存常用筛选
function showCustomConditionDialog(){
	var html = '<label>'+Translator.t('筛选条件名称')+'</label><?=Html::textInput('filter_name',@$_REQUEST['filter_name'],['class'=>'iv-input','id'=>'filter_name']);?>';
	var modalbox = bootbox.dialog({
		title: Translator.t("保存为常用筛选条件"),
		className: "", 
		message: html,
		buttons:{
			Ok: {  
				label: Translator.t("保存"),  
				className: "btn-primary",  
				callback: function () { 
					if ($('#filter_name').val() == "" ){
						bootbox.alert(Translator.t('请输入筛选条件名称!'));
						return false;
					}

					saveCustomCondition(modalbox , $('#filter_name').val() );
					return false;
					//result = ListTracking.AppendRemark(track_no , $('#filter_name').val());
				}
			}, 
			Cancel: {  
				label: Translator.t("返回"),  
				className: "btn-default",  
				callback: function () {  
				}
			}, 
		}
	});	
}
//保存常用筛选条件
function saveCustomCondition(modalbox , filter_name){
	var config = "carrier/carrierprocess";
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:global.baseUrl+'carrier/carrierprocess/append-custom-condition?custom_name='+filter_name+'&configPath='+config, 
			data: $('#form1').serialize(),
			success: function (result) {
				if (result.success == false){
					bootbox.alert(result.message);	
					return false
				}
				modalbox.modal('hide');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
}
</script>