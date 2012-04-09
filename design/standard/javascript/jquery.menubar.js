/********************************************************************************
* 
********************************************************************************/
var $Menubar=(function($){
	if(!$){return;}

	var $$=$.sub();

	var $Self={
			'applyDelimiters':function(){
				$('ul.menu:not(.delimiter) > li:not(:last-child)').addClass('delimiter');
			},
			'removeDelimiters':function(){
				$('ul.menu:not(.delimiter) > li:not(:last-child)').removeClass('delimiter');
			},
			'run':function(){
				$$('ul.menu').menubar();
			}
		}
	;

	var $CollapsibleOptions={
			'MinimumCount':2,
			'MinFinalWidth':200,
			'MaxFinalWidth':400,
			'BeginingElementsToLeaveOpen':0,
			'EndElementsToLeaveOpen':0,
			'PreviewWidth':25,
			'TimeInitialCollapse':600,
			'TimeExpansionAnimation':800,
			'TimeCompressionAnimation':500
		}
	;

	$$.fn.collapsible=function(){
		// check if easing plugin exists; default: swing
		var easing=(typeof(jQuery.easing)=='object') ? 'easeOutQuad' : 'swing';
		$('#path li').filter(':first').addClass('homeicon');

		this.each(function(){
			var Menu=$$(this),
				MenuItems=Menu.children()
			;
			if(MenuItems.length>=$CollapsibleOptions.MinimumCount){
				var LastItem=MenuItems.filter(':last'),
					LastItemWidth=LastItem.width()
				;
				if(LastItemWidth>$CollapsibleOptions.MaxFinalWidth){
					if($CollapsibleOptions.BeginingElementsToLeaveOpen>0){
						$CollapsibleOptions.BeginingElementsToLeaveOpen--;
					}
					if($CollapsibleOptions.EndElementsToLeaveOpen>0){
						$CollapsibleOptions.EndElementsToLeaveOpen--;
					}
				}
				if(LastItemWidth>$CollapsibleOptions.MinFinalWidth && LastItemWidth<$CollapsibleOptions.MaxFinalWidth){
					if($CollapsibleOptions.BeginingElementsToLeaveOpen>0){
						$CollapsibleOptions.BeginingElementsToLeaveOpen--;
					}
				}
				var LastItemIndex=MenuItems.length-1-$CollapsibleOptions.EndElementsToLeaveOpen;
				MenuItems.each(function(Key){
					if(Key>$CollapsibleOptions.BeginingElementsToLeaveOpen && Key<LastItemIndex){
						var Item=$(this),
							Link=Item.find('a')
						;
						Link.on({
							'mouseover':function(e){
								var o=e.data;
								o.item.stop(true, true).animate({'width':o.width}, $CollapsibleOptions.TimeExpansionAnimation, easing);
							},
							'mouseout':function(e){
								var o=e.data;
								o.item.stop(true, true).animate({'width':$CollapsibleOptions.PreviewWidth}, $CollapsibleOptions.TimeCompressionAnimation, easing);
							}
						}, {
							'id':Key,
							'width':Link.width(),
							'item':Link
						}).css({
							'verticalAlign':'middle',
							'overflow':'hidden'
						}).animate({
							'width':$CollapsibleOptions.PreviewWidth
						}, $CollapsibleOptions.TimeInitialCollapse, easing);
					}
				});
			}
		});
		return this;
	};

	$$.fn.menubar=function(){
		this.each(function(){
			var Menu=$$(this),
				Parent=Menu.parent(),
				isSubmenu=Parent.is('li')
			;
			if(isSubmenu){
				Menu.css({'minWidth':Parent.outerWidth()+'px'});
				Parent.hover(function(){
					if(Parent.parent().hasClass('vertical')){
						Menu.css({'left':Parent.outerWidth()+'px','top':0});
					}
					Menu.stop(true, true).slideDown(500);
				},function(){
					Menu.stop(true, true).slideUp(250)
				});
			}
		});
		this.filter('.collapsible').collapsible();
		return this;
	};

	$(function(){
		$Self.applyDelimiters();
		$Self.run();
	});

})(typeof(jQuery)!=='undefined'?jQuery:null);