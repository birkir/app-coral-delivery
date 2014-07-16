$(document).on('ready', function () {

	// Style select boxes
	$('select').select2({
		minimumResultsForSearch: 10
	});

	// Toggle destination carrier on/off
	$('#packageCarrier').on('change', function () {
		if ($(this).find('option:selected').data('express') === true) {
			$('#packageCarrier2').attr('disabled', 'disabled')
		} else {
			$('#packageCarrier2').removeAttr('disabled')
		}
	}).trigger('change');

	// Hide after x time
	$('[data-hide]').each(function () {
		var el = $(this);
		el.addClass('fade in');
		setTimeout(function () {
			el.removeClass('in');
		}, parseInt(el.data('hide'), 0));
	});

	// Remote buttons
	// ---
	$('[data-remote]').on('click', function (e) {
		
		var anchor = $(this),
			confirm_text = anchor.data('confirm'),
			remote = anchor.attr('href');

		function getRemote () {
			$.getJSON(remote, function (response) {
				if (response.status === 200) {
					if ('redirect' in response.data) {
						window.location = response.data.redirect;
					}
				}
			})
		}

		if (confirm_text) {
			if (confirm(confirm_text)) {
				getRemote();
			}
		} else {
			getRemote();
		}

		return false;
	});

	// Package list tablesorting and filtering
	// ---
	$('[data-widget="package/list"]').each(function () {

		// Setup variables
		var form = $(this),
			dataFilters = form.find('[data-filter]'),
			table = form.find('table'),
			filters = [];

		$.tablesorter.addParser({
			id: 'state',
			format: function(s, table, cell, cellIndex) {
				return $(cell).data('state');
			},
			parsed: false,
			type: 'numeric'
		});

		$.tablesorter.addParser({
			id: 'query',
			format: function(s, table, cell, cellIndex) {
				return $(cell).children('a').text() + " " + $(cell).data('description');
			},
			parsed: false,
			type: 'text'
		});

		// Setup tablesorter and init
		table.tablesorter({
			widgets: ["filter"],
			widgetOptions: {
				filter_columnFilters: false,
				filter_useParsedData: true,
				filter_reset: '[data-filter-clear]',
			}
		})
		.tablesorterPager({
			container: table.find('tfoot tr td'),
			output: '{page} / {totalPages}',
			size: 20
		});

		$.tablesorter.filter.bindSearch(table, $('[data-filter=query]'));

		table.bind('filterEnd', function() {
			filters = $.tablesorter.getFilters(table);
			dataFilters.filter('[data-filter=5]').parent().removeClass('active');
			if (dataFilters.filter('[data-filter=5][href="#'+filters[5]+'"]').eq(0).parent().addClass('active').length === 0) {
				dataFilters.filter('[data-filter=5:eq(0)').parent().addClass('active');
			}
			if (filters[1] === '' && dataFilters.filter('[data-filter=2]').val() !== '') {
				dataFilters.filter('[data-filter=2]').val('').trigger('change');
			}
			if (filters[2] === '' && dataFilters.filter('[data-filter=3]').val() !== '') {
				dataFilters.filter('[data-filter=3]').val('').trigger('change');
			}
			$('.mobile-filters-toggle').trigger('click');
		});

		table.find('td[data-description]').each(function () {

			var view = '';
			if ($(this).data('photo') != '') {
				view += '<img src="' + $(this).data('photo') + '" alt="" class="pull-left img-thumbnail" width="100"><p style="margin-left: 120px">';
			} else {
				view += '<p>';
			}
			view += '' + $(this).data('description') + '</p><div class="clearfix"></div>';

			$(this).popover({
				container: 'body',
				content: view,
				html: true,
				trigger: 'hover'
			});
		})

		// Bind click event to anchor filters
		dataFilters.filter('a').on('click', function () {
			var filter = this.href.substr(this.href.indexOf('#') + 1);
			filters = $.tablesorter.getFilters(table);
			filters[$(this).data('filter')] = (filter != '0' ? filter : '');
			$.tablesorter.setFilters(table, filters, true);
			return false;
		});

		// Bind keyup event to select filters
		dataFilters.filter('select').on('change', function () {
			filters[$(this).data('filter')] = $(this).find('option:selected').text();
			$.tablesorter.setFilters(table, filters, true);
		});

		$('.mobile-filters-toggle').on('click', function () {

			var trigger = $(this),
				txt = trigger.data('toggle-text'),
				toToggle = trigger.parent().next();

			trigger.data('toggle-text', trigger.text());
			trigger.text(txt);
			toToggle.toggleClass('hidden-xs');
		})
	});

	// Account Profile specific
	// ---
	$('[data-widget="account/profile"]').each(function() {

		var form = $(this);

		// Add and wait for zxcvbn script
		$.getScript('//dl.dropbox.com/u/209/zxcvbn/zxcvbn.js', function () {

			// Email change helper message toggle
			form.find('#profileEmail').on('keyup', function () {
				var input = $(this), val = input.val(), ori = input.data('original');
				input.next()[(val === ori) ? 'addClass' : 'removeClass']('hidden');
			});

			// Password keyup confirmation and strength check
			form.find('#profilePassword').on('keyup', function () {
				var input = $(this), empty = (input.val().length === 0);
				form.find('.password-not-empty')[empty ? 'addClass' : 'removeClass']('hidden');

				if (empty) {
					form.find('.pwd-error, .pwd-confirm').addClass('hidden');
				}

				if (typeof zxcvbn !== 'undefined') {
					var res = zxcvbn(input.val());
					form.find('.strength').removeClass('s0 s1 s2 s3 s4').addClass('s' + res.score);
					form.find('.crack-time').html(res.crack_time_display);
				}
			});

			// Show confirm password message
			form.find('#profilePasswordConfirm').on('blur', function () {
				var matches = ($(this).val() === form.find('#profilePassword').val());
				form.find('.pwd-error, .pwd-confirm').addClass('hidden');
				if (matches) form.find('.pwd-confirm').removeClass('hidden');
				else form.find('.pwd-error').removeClass('hidden');
			});

		});

	});

	// Hook Builder
	// ----
	$('[data-widget=hookbuilder]').each(function () {

		var form = $(this),
			logics = ['AND', 'OR'],
			operators = ['=', '!=', '<>', '<', '<=', '>', '>=', '~='],
			fields = ['message', 'location', 'timestamp'],
			dataSet = [],
			tmpElement;

		// Create new group for new conditions or nested groups.
		function addGroup() {

			var builderGroup = $('<div>', { 'class': 'builder-group' }).appendTo(this),
				formGroup = $('<div>', { 'class': 'form-group' }).appendTo(builderGroup),
				builderConditions = $('<div>', { 'class': 'builder-conditions' }).appendTo(builderGroup);

			// Create logic selector
			var logicSelect = $('<select>', { 'class': 'form-control' }).on('change', renderJSON);

			for (var i = 0; i < logics.length; i++) {

				// Add logic options to select
				logicSelect.append($('<option>').text(logics[i]));
			}

			// Create 2 column wide div and attach to form group
			$('<div>', { 'class': 'col-sm-2'}).append(logicSelect).appendTo(formGroup);

			// Run select2
			logicSelect.select2({ minimumResultsForSearch: 100 });

			// Create buttons
			var addConditionButton = $('<a>', { 'href': '#', 'class': 'btn btn-success' }).text('Add condition').on('click', function () {
				builderConditions.each(addCondition);
				return false;
			});

			var addGroupButton = $('<a>', { 'href': '#', 'class': 'btn btn-success' }).text('Add group').on('click', function () {
				builderConditions.each(addGroup);
				return false;
			});
			var removeGroupButton = $('<a>', { 'href': '#', 'class': 'btn btn-danger' }).text('Remove group').on('click', function () {
				if (confirm('Are you sure?')) {
					$(this).parent().parent().parent().remove();
					renderJSON();
				}
				return false;
			});

			// Create toolbar
			$('<div>', { 'class' : 'btn-toolbar'})
			.append(addConditionButton)
			.append(addGroupButton)
			.append(removeGroupButton)
			.appendTo(formGroup);

			// Render JSON
			renderJSON();

			tmpElement = builderGroup;
		}

		function addCondition() {

			// Create field group
			var formGroup = $('<div>', { class: 'form-group builder-condition' }).appendTo(this);

			// Create field select
			var fieldSelect = $('<select>', { 'class': 'form-control' }).on('change', renderJSON);

			for (var i = 0; i < fields.length; i++) {

				// Add logic options to select
				fieldSelect.append($('<option>').text(fields[i]));
			}

			// Create 3 column wide div and attach to form group
			$('<div>', { 'class': 'col-sm-4 builder-c-field'}).append(fieldSelect).appendTo(formGroup);

			fieldSelect.select2({ minimumResultsForSearch: 15 });

			// Create field select
			var operatorSelect = $('<select>', { 'class': 'form-control' }).on('change', renderJSON);

			for (var i = 0; i < operators.length; i++) {

				// Add logic options to select
				operatorSelect.append($('<option>').text(operators[i]));
			}

			// Create 1 column wide div and attach to form group
			$('<div>', { 'class': 'col-sm-2 builder-c-op'}).append(operatorSelect).appendTo(formGroup);

			operatorSelect.select2({ minimumResultsForSearch: 15 });

			// Create expression field
			var expressionField = $('<input>', { 'type': 'text', 'class': 'form-control expression', 'placeholder': 'Expression(s)'}).on('change', renderJSON);

			// Create 3 column wide div and attach to form group
			$('<div>', { 'class': 'col-sm-6 builder-c-expression'}).append(expressionField).appendTo(formGroup);

			// Add remove button
			$('<a>', { 'href': '#', 'class': 'btn btn-danger' }).html('&times;').appendTo(formGroup).on('click', function () {
				if (confirm('Are you sure?')) {
					$(this).parent().remove();
					renderJSON();
				}
				return false;
			});

			// Render JSON
			renderJSON();

			tmpElement = formGroup;
		};

		// Render JSON to input field
		function renderJSON() {
			
			// Get builder data
			var data = builderToJSON(form.children('.builder-group')[0]);

			// Set hidden input data
			$('[name=trigger]').val(JSON.stringify([data]));
		}

		// Create JSON for trigger
		function builderToJSON(el) {

			// Get group and setup data object
			var group = $(el),
				data = {};

			// Set data attributes
			data.type = 'group';
			data.operator = group.children('.form-group').find('select').val();
			data.children = [];

			// Loop through conditions
			group.children('.builder-conditions').children('.builder-condition').each(function () {

				var condition = {};

				// Set condition attributes
				condition.type = 'condition';
				condition.field = $(this).find('select').eq(0).val();
				condition.operator = $(this).find('select').eq(1).val();
				condition.expression = $(this).find('.expression').val();

				// Add condition to data children
				data.children.push(condition);
			});

			// Loop through groups
			group.children('.builder-conditions').children('.builder-group').each(function () {
				data.children.push(builderToJSON(this));
			});

			return data;
		}

		function JSONToBuilder (container, data) {

			for (var i = 0; i < data.length; i++) {
				
				var item = data[i];

				if (item.type === 'group') {

					container.each(addGroup);
					tmpElement.children('.form-group').find('select option[value=' + item.operator + ']').attr('selected', 'selected');
					JSONToBuilder (tmpElement, item.children);

				} else if (item.type === 'condition') {

					container.children('.builder-conditions').each(addCondition);
					tmpElement.find('select').eq(0).val(item.field).trigger('change');
					tmpElement.find('select').eq(1).val(item.operator).trigger('change');
					tmpElement.find('input.expression').val(item.expression);
				}
			}
		}

		// Attach build callback
		if ($('[name=trigger]').val().length === 0) {
			form.each(addGroup);
		} else {
			var val = $('[name=trigger]').val();
			val = val.replace(/\&quot\;/, '"');
			JSONToBuilder(form, JSON.parse(val));
		}
	});

	$('[data-chart]').each(function () {

		var container = $(this),
			element = this,
			chartType = container.data('chart'),
		    chartData = window[container.data('json')] || {};

		var data = [['Origin and destination', 'Collection', 'In transit']];

		for (var name in chartData) {
			data.push([name, chartData[name].collecting, chartData[name].transit]);
		}

      	google.setOnLoadCallback(function () {
			data = google.visualization.arrayToDataTable(data);
	        var chart = new google.visualization.ColumnChart(element);
	        chart.draw(data, {
	        	isStacked: true,
				title: 'Transfer time',
				hAxis: {
					title: 'Days'
				}
			});
      	});

	});

	$(window).trigger('resize');
});

$(window).on('resize', function () {
	$('.floating').each(function (){ 
		window.scrollTrigger = $(this).offset().top - 50;
	})
})

$(window).on('scroll', function () {
	if (this.scrollTrigger) {
		$('.floating')[(window.scrollY > this.scrollTrigger) ? 'addClass' : 'removeClass']('active');
	}
})