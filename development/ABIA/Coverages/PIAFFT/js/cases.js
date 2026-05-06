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
			$(".rstacks").removeClass('animated zoomIn');
			$(".rstacks").removeClass('animated fadeIn');
			$('.rfft-text').removeClass('animated bounceOutLeft');
			$('.rfft-text').removeClass('animated zoomIn');
			$('.rloss-text').removeClass('animated zoomIn');
		});
		$('select#RWBBR').on('change', function() {
			$(".rstacks").removeClass('zoomIn');
			$(".rstacks").removeClass('animated');
			$(".rstacks").removeClass('animated zoomIn');
			$('.rfft-text').removeClass('animated bounceOutLeft');
			$('.rloss-text').removeClass('animated zoomIn');
			if ($('select#RWBBR option:selected').val() == 'Select') {
				$('#rFFTLoss').val("$0");
				$('.rloss-text').addClass('animated zoomIn')
				$('.rfft-text').text("$0").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/0.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '1') {
				$('#rFFTLoss').val("$105,000");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$105,000").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/105k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '2') {
				$('#rFFTLoss').val("$85,000");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$85,000").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/85k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '3') {
				$('#rFFTLoss').val("$63,000");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$63,000").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/63k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '4') {
				$('#rFFTLoss').val("$42,000");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$42,000").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/42k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '5') {
				$('#rFFTLoss').val("$31,500");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$31,500").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/31.5k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '6') {
				$('#rFFTLoss').val("$21,000");
				$('.rloss-text').addClass('animated zoomIn');
				$('.rfft-text').text("$21,000").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/21k.png').addClass('animated zoomIn');
			}
			if ($('select#RWBBR option:selected').val() == '7') {
				$('.rloss-text').addClass('animated zoomIn');
				$('#rFFTLoss').val("$10,500");
				$('.rfft-text').text("$10,500").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/10.5k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '8') {
				$('.rloss-text').addClass('animated zoomIn');
				$('#rFFTLoss').val("$5,500");
				$('.rfft-text').text("$5,500").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/5.5k.png').addClass('animated zoomIn').fadeIn("slow");
			}
			if ($('select#RWBBR option:selected').val() == '9') {
				$('.rloss-text').addClass('animated zoomIn');
				$('#rFFTLoss').val("$2,100");
				$('.rfft-text').text("$2,100").addClass('animated zoomIn');
				$('.rstacks').attr('src', 'images/ransom/2.1k.png').addClass('animated zoomIn').fadeIn("slow");
			}
		});*/

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
