jQuery(function($) {
	if ($(".mmtowop_lvl").length){
		
		$('.mmtowp_active').find('a').eq(0).css({
			'font-weight':'bold',
			'text-decoration':'underline'
		});
		
		$('.mmtowp_active').find('.mmtowp_link').eq(0).css({
			'font-weight':'bold',
			'text-decoration':'underline'
		});
		
		$(".mmtowop_lvl").each(function(){
			if ($(this).find('.mmtowop_lvl').length){
				$(this).prepend('<span class="mmtowp_expand mmtowp_expand_off">⮜</span>');
			}
		});
		
		$('.mmtowp_expand').click(function(e){
			var mmtowp_level=parseInt($(this).parent().attr('data-level'));
			// console.log('mmtowp_level:'+mmtowp_level);
			if ($(this).hasClass('mmtowp_expand_off')) {
				$(this).text('⮟').removeClass('mmtowp_expand_off').addClass('mmtowp_expand_on');
				$(this).parent().find('[data-level="'+parseInt(mmtowp_level+1)+'"]').css('display','inline-block');
			}
			else {
				$(this).text('⮜').removeClass('mmtowp_expand_on').addClass('mmtowp_expand_off');
				$(this).parent().find('[data-level="'+parseInt(mmtowp_level+1)+'"]').css('display','none');
			}
		});
		
		if ($("#mmtowp_allexpand").length){
			$('.mmtowp_expand').each(function(){
				$(this).click();
			});
		}	
	}
});

document.addEventListener("DOMContentLoaded", function(event) {
	var classname = document.getElementsByClassName("mmtowp_link");
	for (var i = 0; i < classname.length; i++) {
		classname[i].addEventListener('click', mmtowpleftclic, false);
		classname[i].addEventListener('contextmenu', mmtowprightclic, false);
	}
});

var mmtowpleftclic = function(event) {
	var attribute = this.getAttribute("data-mmtowplink");               
	if(event.ctrlKey) {                   
		var newWindow = window.open(decodeURIComponent(window.atob(attribute)), '_blank');                    
		newWindow.focus();               
	} else {                    
		document.location.href= decodeURIComponent(window.atob(attribute));
	}
};

var mmtowprightclic = function(event) {
	var attribute = this.getAttribute("data-mmtowplink");               
	if(event.ctrlKey) {                   
		var newWindow = window.open(decodeURIComponent(window.atob(attribute)), '_blank');                    
		newWindow.focus();               
	} else {      
		window.open(decodeURIComponent(window.atob(attribute)),'_blank');	
	}
} 	