/**
 * @author th
 */

/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	if (typeof ThemeHouse === "undefined") ThemeHouse = {};

	// *********************************************************************

	/**
	 * Special effect that allows positioning based on bottom / left rather than top / left
	 */
	$.tools.tooltip.addEffect('VerseTooltip',
	function(callback)
	{
		var triggerOffset = this.getTrigger().offset(),
			config = this.getConf(),
			css = {
				top: 'auto',
				bottom: $(window).height() - triggerOffset.top + config.offset[0]
			},
			narrowScreen = ($(window).width() < 480);

		if (XenForo.isRTL())
		{
			css.right = $('html').width() - this.getTrigger().outerWidth() - triggerOffset.left - config.offset[1];
			css.left = 'auto';
		}
		else
		{
			css.left = triggerOffset.left + config.offset[1];
			if (narrowScreen)
			{
				css.left = Math.min(50, css.left);
			}
		}

		this.getTip().css(css).xfFadeIn(XenForo.speed.normal);

	},
	function(callback)
	{
		this.getTip().xfFadeOut(XenForo.speed.fast);
	});

	/**
	 * Cache to store fetched verses
	 *
	 * @var object
	 */
	ThemeHouse._VerseTooltipCache = {};

	ThemeHouse.VerseTooltip = function($el)
	{
		var hasTooltip, verseUrl, setupTimer;
		
		if (!parseInt(XenForo._enableOverlays))
		{
			return;
		}

		if (!(verseUrl = $el.data('verseurl')))
		{
			console.warn('Verse tooltip has no verse: %o', $el);
			return;
		}

		$el.find('[title]').andSelf().attr('title', '');

		$el.bind(
		{
			mouseenter: function(e)
			{
				if (hasTooltip)
				{
					return;
				}

				setupTimer = setTimeout(function()
				{
					if (hasTooltip)
					{
						return;
					}

					hasTooltip = true;

					var $tipSource = $('#VerseTooltip'),
						$tipHtml,
						xhr;

					if (!$tipSource.length)
					{
						console.error('Unable to find #VerseTooltip');
						return;
					}

					console.log('Setup verse tooltip for %s', verseUrl);

					$tipHtml = $tipSource.clone()
						.removeAttr('id')
						.addClass('xenVerseTooltip')
						.appendTo(document.body);

					if (!ThemeHouse._VerseTooltipCache[verseUrl])
					{
						xhr = XenForo.ajax(
							verseUrl,
							{},
							function(ajaxData)
							{
								if (XenForo.hasTemplateHtml(ajaxData))
								{
									ThemeHouse._VerseTooltipCache[verseUrl] = ajaxData.templateHtml;

									$(ajaxData.templateHtml).xfInsert('replaceAll', $tipHtml.find('.VerseContents'));
								}
								else
								{
									$tipHtml.remove();
								}
							},
							{
								type: 'GET',
								error: false,
								global: false
							}
						);
					}

					$el.tooltip(XenForo.configureTooltipRtl({
						predelay: 500,
						delay: 0,
						effect: 'VerseTooltip',
						fadeInSpeed: 'normal',
						fadeOutSpeed: 'fast',
						tip: $tipHtml,
						position: 'bottom left',
						offset: [ 10, -15 ] // was 10, 25
					}));

					$el.data('tooltip').show(0);

					if (ThemeHouse._VerseTooltipCache[verseUrl])
					{
						$(ThemeHouse._VerseTooltipCache[verseUrl])
							.xfInsert('replaceAll', $tipHtml.find('.VerseContents'), 'show', 0);
					}
				}, 800);
			},

			mouseleave: function(e)
			{
				if (hasTooltip)
				{
					if ($el.data('tooltip'))
					{
						$el.data('tooltip').hide();
					}

					return;
				}

				if (setupTimer)
				{
					clearTimeout(setupTimer);
				}
			},

			mousedown: function(e)
			{
				// the click will cancel a timer or hide the tooltip
				if (setupTimer)
				{
					clearTimeout(setupTimer);
				}

				if ($el.data('tooltip'))
				{
					$el.data('tooltip').hide();
				}
			}
		});
	};
	
	if (!XenForo.isTouchBrowser())
	{
		// Register tooltip elements for desktop browsers
		XenForo.register('.VerseTooltip', 'ThemeHouse.VerseTooltip');
	}

}
(jQuery, this, document);