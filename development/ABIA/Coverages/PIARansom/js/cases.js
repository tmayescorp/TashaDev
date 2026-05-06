/** JS CASES */
$(document).ready(function() {
	$(".stacks").removeClass('zoomIn');
	$(".stacks").removeClass('animated');
	$(".rstacks").removeClass('zoomIn');
	$(".rstacks").removeClass('animated');
	
	$(document).on('click touch', function() {
		$(".stacks").removeClass('zoomIn');
		$(".stacks").removeClass('animated');
		$(".rstacks").removeClass('zoomIn');
		$(".rstacks").removeClass('animated');
		/*FFT Transfer*/
		$('select#BBR').click(function() {
			$(".stacks").removeClass('animated zoomIn');
			$(".stacks").removeClass('animated fadeIn');
			$('.fft-text').removeClass('animated bounceOutLeft');
			$('.fft-text').removeClass('animated zoomIn');
			$('.loss-text').removeClass('animated zoomIn');
		});
		$('select#BBR').on('change', function() {
			$(".stacks").removeClass('zoomIn');
			$(".stacks").removeClass('animated');
			$(".stacks").removeClass('animated zoomIn');
			$('.fft-text').removeClass('animated bounceOutLeft');
			$('.loss-text').removeClass('animated zoomIn');
			if ($('select#BBR option:selected').val() == 'Select') {
				$('#FFTLoss').val("$0");
				$('input#FFTLoss').val("$0");
				$('.loss-text').addClass('animated zoomIn')
				$('.fft-text').text("$0").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/0.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('Select an industry to see a example<br/>');
			}
			if ($('select#BBR option:selected').val() == '1') {
				$('#FFTLoss').val("$393,000");
				$('input#FFTLoss').val("$393,000");
				$('.loss-text').addClass('animated zoomIn');
				$('.fft-text').text("$393,000").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/393.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('An escrow company owner normally received emails from her bank when a wire transfer was completed. Hackers broke into the company’s systems, captured online banking credentials, disabled email notifications and issued 26 fraudulent wire transfers, stealing $393,000. <br/><small>Source: www.aciworldwide.com</small>');
			}
			if ($('select#BBR option:selected').val() == '2') {
				$('#FFTLoss').val("$50,000");
				$('input#FFTLoss').val("$50,000");
				$('.loss-text').addClass('animated zoomIn');
				$('.fft-text').text("$50,000").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/50.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('Two fraudulent wire transfers totaling $150,000 were issued from a small import company’s bank account. Malware was discovered on the company’s computer system, enabling criminals to do fraudulent wire transfers. The bank retrieved one of the wires, but the business lost $50,000. <br/> <small>Source: www.cbsnews.com</small>');
			}
			if ($('select#BBR option:selected').val() == '3') {
				$('#FFTLoss').val("$90,000");
				$('input#FFTLoss').val("$90,000");
				$('.loss-text').addClass('animated zoomIn');
				$('.fft-text').text("$90,000").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/90k.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('The owner of a small retailer that regularly wired money discovered an unauthorized wire transfer of $90,000 issued to a bank in Latvia. Authorities were called and it was discovered that the business’ systems were infected with a trojan virus that gave criminals access to the firm’s online banking system. <br/><small>Source: www.theregister.co.uk</small>');
			}
			if ($('select#BBR option:selected').val() == '4') {
				$('#FFTLoss').val("$550,000");
				$('input#FFTLoss').val("$550,000");
				$('.loss-text').addClass('animated zoomIn');
				$('.fft-text').text("$550,000").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/550k.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('A supplier’s employee opened an email appearing to be from the company’s bank and clicked on a link that seemed to be the bank’s website, but was really a scam set up by the phishers. The employee provided online banking credentials and the bank’s security token code. With the stolen credentials, the thieves wired $550,000 out of the company’s account. <br/><small>Source: www.bankinfosecurity.com</small>');
			}
			if ($('select#BBR option:selected').val() == '5') {
				$('#FFTLoss').val("$125,000");
				$('.loss-text').addClass('animated zoomIn');
				$('.fft-text').text("$125,000").addClass('animated zoomIn');
				$('.stacks').attr('src', 'images/125.png').addClass('animated zoomIn').fadeIn("slow");
				$('.show-text').html('Hackers broke into a construction company’s account, stealing $125,000 via ACH using an employee’s user name and password. The employee’s computer was infected with malware when she visited a social media site. <br/><small> Source: www.nytimes.com</small>')
			}
		});

		/*$("#RWBBR").click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			
		});*/

/*Ransomware */

		/*if (/Mobi/.test(navigator.userAgent)) {
		       $('#display-images').css('display', 'none');
			   if (/i(Phone)/.test(navigator.userAgent)) {
					$('#display-images').css('display', 'block');
				}
		    }
		 if (/i(Phone)/.test(navigator.userAgent)) {
					$('#display-images').css('display', 'block');
				}
				*/

		$('.33blk').click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			$('.33blk').attr('src', 'images/33red.png').addClass('red animated zoomIn').fadeIn("slow");
			$('.59blk').attr('src', 'images/59blk.png').addClass('').fadeIn("slow");
			$('.1in5blk').attr('src', 'images/1in5blk.png').addClass('').fadeIn("slow");
			$('.1077blk').attr('src', 'images/1077blk.png').addClass('').fadeIn("slow");
			$('.2daysblk').attr('src', 'images/2daysblk.png').addClass('').fadeIn("slow");
			$('.show-text').html("<center>Ransomware hits 1/3 of small/medium businesses worldwide</center>");
			$('.showimage').attr('src', 'images/33image.png').addClass('animated zoomIn').fadeIn("slow");
			$('.source').html("Source <br><a href='https://www.cnet.com/news/malwarebytes-state-of-ransomware-shutting-down-1-in-5-affected-small-businesses/' target='_blank'>https://www.cnet.com/news/malwarebytes-state-of-ransomware-shutting-down-1-in-5-affected-small-businesses/</a>");
		});

		$('.59blk').click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			$('.59blk').attr('src', 'images/59red.png').addClass('red animated zoomIn').fadeIn("slow");
			$('.33blk').attr('src', 'images/33blk.png').addClass('').fadeIn("slow");
			$('.1in5blk').attr('src', 'images/1in5blk.png').addClass('').fadeIn("slow");
			$('.1077blk').attr('src', 'images/1077blk.png').addClass('').fadeIn("slow");
			$('.2daysblk').attr('src', 'images/2daysblk.png').addClass('').fadeIn("slow");
			$('.show-text').html("<ul class='rfft-list'><li>59% of ransomware attacks come through email</li><li>You can still be a victim of ransomware even if you don't have an online business");
			$('.showimage').attr('src', 'images/59image.png').addClass('animated zoomIn').fadeIn("slow");
			$('.source').html("Source <br><a href='http://invenioit.com/security/ransomware-statistics-2016/' target='_blank'>http://invenioit.com/security/ransomware-statistics-2016/</a>");
		});

		$('.1in5blk').click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			$('.59blk').attr('src', 'images/59blk.png').addClass('').fadeIn("slow");
			$('.33blk').attr('src', 'images/33blk.png').addClass('').fadeIn("slow");
			$('.1in5blk').attr('src', 'images/1in5red.png').addClass('red animated zoomIn').fadeIn("slow");
			$('.1077blk').attr('src', 'images/1077blk.png').addClass('').fadeIn("slow");
			$('.2daysblk').attr('src', 'images/2daysblk.png').addClass('').fadeIn("slow");
			$('.show-text').html("<ul class='rfft-list'><li>1 in 5 businesses that pay the ransom never get their files back</li><li>Many businesses that pay the ransom are targeted again</li><li>Every situation needs to be evaluated with a cyber forensics expert</li></ul>");
			$('.showimage').attr('src', 'images/1in5image.png').addClass('animated zoomIn').fadeIn("slow");
			$('.source').html("Source <br><a href='https://blog.barkly.com/ransomware-statistics-2017' target='_blank'>https://blog.barkly.com/ransomware-statistics-2017</a>");
		});

		$('.1077blk').click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			$('.59blk').attr('src', 'images/59blk.png').addClass('').fadeIn("slow");
			$('.33blk').attr('src', 'images/33blk.png').addClass('').fadeIn("slow");
			$('.1in5blk').attr('src', 'images/1in5blk.png').addClass('').fadeIn("slow");
			$('.1077blk').attr('src', 'images/1077red.png').addClass('red animated zoomIn').fadeIn("slow");
			$('.2daysblk').attr('src', 'images/2daysblk.png').addClass('').fadeIn("slow");
			$('.show-text').html("<ul class='rfft-list'><li>Average ransom demand = $1,077</li><li>Most demands are small to encourage quick payment</li></ul>");
			$('.showimage').attr('src', 'images/1077image.png').addClass('animated zoomIn').fadeIn("slow");
			$('.source').html("Source <br><a href='https://blog.barkly.com/ransomware-statistics-2017'  target='_blank'>https://blog.barkly.com/ransomware-statistics-2017</a>");
		});
		$('.2daysblk').click(function() {
			if ($("#RWBBR img").hasClass('animated zoomIn')) {
				$("#RWBBR img").removeClass('animated zoomIn');
			}
			if ($(".showimage").hasClass('animated zoomIn')) {
				$(".showimage").removeClass('animated zoomIn');

			}
			$('.59blk').attr('src', 'images/59blk.png').addClass('').fadeIn("slow");
			$('.33blk').attr('src', 'images/33blk.png').addClass('').fadeIn("slow");
			$('.1in5blk').attr('src', 'images/1in5blk.png').addClass('').fadeIn("slow");
			$('.1077blk').attr('src', 'images/1077blk.png').addClass('').fadeIn("slow");
			$('.2daysblk').attr('src', 'images/2daysred.png').addClass('red animated zoomIn').fadeIn("slow");
			$('.showimage').attr('src', 'images/2daysimage.png').addClass('animated zoomIn').fadeIn("slow");
			$('.show-text').html("<ul class='rfft-list'><li>Most businesses face at least 2 days of downtime</li><li>Each hour of downtime = $8,581 on average</li></ul>");
			$('.source').html("Source <br><a href='http://invenioit.com/security/ransomware-statistics-2016/' target='_blank'>http://invenioit.com/security/ransomware-statistics-2016/</a>");

});
		/*Fraud Model */
		$("#FraudBBR").click(function() {
			$(".rstacks").removeClass('animated zoomIn');
			$(".rstacks").removeClass('animated fadeIn');
			$('.rfft-text').removeClass('animated bounceOutLeft');
			$('.rfft-text').removeClass('animated zoomIn');
			$('.rloss-text').removeClass('animated zoomIn');
		});
		$('select#FraudBBR').on('change',function() { 
    		var currVal = $(this).val();
			$(".stacks").removeClass('zoomIn');
			$(".stacks").removeClass('animated');
			$(".stacks").removeClass('animated zoomIn');
			$('.fraud-text').removeClass('animated bounceOutLeft');
			$('.loss-text').removeClass('animated zoomIn');

			if (navigator.userAgent.toLowerCase().match(/(ipad|iphone)/)) {
    		//is safari
	    		if ($(this).val() == 'Select') {
					$('.value-fraud').val("$0").focus();
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$0").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/0.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('<center>Select an industry to see a example</center><br/>');
				}
				if ($('select#FraudBBR option:selected').val() == '1') {
					$('.value-fraud').val("$393,000").focus();
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$393,000").addClass('animated zoomIn');
					$('.show-text').html('An escrow company owner normally received emails from her bank when a wire transfer was completed. Hackers broke into the company’s systems, captured online banking credentials, disabled email notifications and issued 26 fraudulent wire transfers, stealing $393,000. <br/><small>Source: www.aciworldwide.com</small>');
					$('.stacks').attr('src', 'images/393.png').addClass('animated zoomIn').fadeIn("slow");
				}
				if ($('select#FraudBBR option:selected').val() == '2') {
					$('.value-fraud').val("$50,000").focus();
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$50,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/50.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('Two fraudulent wire transfers totaling $150,000 were issued from a small import company’s bank account. Malware was discovered on the company’s computer system, enabling criminals to do fraudulent wire transfers. The bank retrieved one of the wires, but the business lost $50,000. <br/> <small>Source: www.cbsnews.com</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '3') {
					$('.value-fraud').val("$90,000").focus();
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$90,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/90k.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('The owner of a small retailer that regularly wired money discovered an unauthorized wire transfer of $90,000 issued to a bank in Latvia. Authorities were called and it was discovered that the business’ systems were infected with a trojan virus that gave criminals access to the firm’s online banking system. <br/><small>Source: www.theregister.co.uk</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '4') {
					$('.value-fraud').val("$550,000").focus();
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$550,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/550k.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('A supplier’s employee opened an email appearing to be from the company’s bank and clicked on a link that seemed to be the bank’s website, but was really a scam set up by the phishers. The employee provided online banking credentials and the bank’s security token code. With the stolen credentials, the thieves wired $550,000 out of the company’s account. <br/><small>Source: www.bankinfosecurity.com</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '5') {
					$('.value-fraud').val("$125,000").focus();
					$('.rloss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$125,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/125.png').addClass('animated zoomIn').fadeIn("slow");
				    $('.show-text').html('A construction company’s computer was infected with malware when an employee visited a social media site, allowing hackers to steal her login credentials and break into the company’s bank account, stealing $125,000 via ACH. <br/><small> Source: www.nytimes.com</small>');
				}
			}
			else {
				if ($(this).val() == 'Select') {
					$('.value-fraud').val("$0");
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$0").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/0.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('<center>Select an industry to see a example</center><br/>');
				}
				if ($('select#FraudBBR option:selected').val() == '1') {
					$('.value-fraud').val("$393,000");
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$393,000").addClass('animated zoomIn');
					$('.show-text').html('An escrow company owner normally received emails from her bank when a wire transfer was completed. Hackers broke into the company’s systems, captured online banking credentials, disabled email notifications and issued 26 fraudulent wire transfers, stealing $393,000. <br/><small>Source: www.aciworldwide.com</small>');
					$('.stacks').attr('src', 'images/393.png').addClass('animated zoomIn').fadeIn("slow");
				}
				if ($('select#FraudBBR option:selected').val() == '2') {
					$('.value-fraud').val("$50,000");
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$50,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/50.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('Two fraudulent wire transfers totaling $150,000 were issued from a small import company’s bank account. Malware was discovered on the company’s computer system, enabling criminals to do fraudulent wire transfers. The bank retrieved one of the wires, but the business lost $50,000. <br/> <small>Source: www.cbsnews.com</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '3') {
					$('.value-fraud').val("$90,000");
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$90,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/90k.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('The owner of a small retailer that regularly wired money discovered an unauthorized wire transfer of $90,000 issued to a bank in Latvia. Authorities were called and it was discovered that the business’ systems were infected with a trojan virus that gave criminals access to the firm’s online banking system. <br/><small>Source: www.theregister.co.uk</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '4') {
					$('.value-fraud').val("$550,000");
					$('.loss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$550,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/550k.png').addClass('animated zoomIn').fadeIn("slow");
					$('.show-text').html('A supplier’s employee opened an email appearing to be from the company’s bank and clicked on a link that seemed to be the bank’s website, but was really a scam set up by the phishers. The employee provided online banking credentials and the bank’s security token code. With the stolen credentials, the thieves wired $550,000 out of the company’s account. <br/><small>Source: www.bankinfosecurity.com</small>');
				}
				if ($('select#FraudBBR option:selected').val() == '5') {
					$('.value-fraud').val("$125,000");
					$('.rloss-text').addClass('animated zoomIn');
					$('.fraud-text').text("$125,000").addClass('animated zoomIn');
					$('.stacks').attr('src', 'images/125.png').addClass('animated zoomIn').fadeIn("slow");
				    $('.show-text').html('A construction company’s computer was infected with malware when an employee visited a social media site, allowing hackers to steal her login credentials and break into the company’s bank account, stealing $125,000 via ACH.<br/><small> Source: www.nytimes.com</small>');
				}
			}
		});
	});
});
