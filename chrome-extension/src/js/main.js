jQuery(document).ready(function($) {

  loadForm();

  $('#popout').on('click', function () {
    chrome.windows.create({
      url: './page.html',
      type: 'panel',
      width: 600,
      height: 600
    });
  });

  $('#selectButton').on('click', function () {
    $('#wikidoc').select();
  });

  $('#url').on('change', function () {
    var url = $(this).val();
    if (!/^http(s)?:\/\//.test(url)) {
      url = 'http://' + url;
    }

    $('input, select, textarea').each(function () {
      $(this).val('');
    });

    $('#url').val(url);

    var a = $('<a>', { href: url })[0];
    $('#host').val(a.hostname);
    checkDomain();

    $('#fullpath').val(a.pathname);
    $('#fullquery').val(a.search);

    a.pathname.split('/').forEach(function (param) {
      if (param) {
        $('#pathparams').children().last().val(param);
        checkPathParams();
      }
    });

    a.search.substring(1).split('&').forEach(function (param) {
      if (param) {
        $('#queryparams').children().last().val(param);
        checkQueryParams();
      }
    });

    saveForm();
  });

  $(document).on('keyup', '.pathparam', function (e) {
    var code = e.keyCode || e.which;
    if (code != '9') { checkPathParams(); }
  });
  $(document).on('keyup', '.queryparam', function (e) {
    var code = e.keyCode || e.which;
    if (code != '9') { checkQueryParams(); }
  });
  $(document).on('change', 'select', generateWikidoc);
  $(document).on('keyup', 'input, .form textarea', generateWikidoc);
  $('#host').on('change', checkDomain);

  function checkPathParams() {
    $('.pathparam').each(function (e) {
      if (!$(this).val()) { $(this).remove(); }
    });
    $('#pathparams').append($('<input>', {
      type: 'text',
      class: 'pathparam',
      placeholder: 'Paramètre du path'
    }));
  }

  function checkQueryParams() {
    $('.queryparam').each(function () {
      if (!$(this).val()) { $(this).remove(); }
    });
    $('#queryparams').append($('<input>', {
      type: 'text',
      class: 'queryparam',
      placeholder: 'Paramètre de la query'
    }));
  }

  function checkDomain() {
    var domain = $('#host').val();

    if (!domain) { return $('#domain-info').empty(); }
    $('#domain-info').text('Vérification du domaine...');

    var match = /\.(com|fr|net|org)(\..*)/.exec(domain);
    if (match) {
      var warning = 'Le domaine semble contenir un suffixe de proxy (' + match[2] + '), pensez à le retirer !';
      $('#proxy-warning').text(warning).show();
      $('#host').focus();
    } else {
      $('#proxy-warning').hide();
    }

    var ezpaarseDomainURL = 'http://ezpaarse-preprod.couperin.org/info/domains/' + domain;

    $.get(ezpaarseDomainURL)
    .done(function (result) {
      if (typeof result !== 'object') { return $('#domain-info').text('Échec de la vérification'); }

      var link = $('<a target="_blank"></a>');
      link.text(result.platformName);
      link.attr('href', result.manifest.docurl);

      $('#domain-info').text('Domaine déjà analysé : ').append(link);
    })
    .fail(function (jqxhr) {
      if (jqxhr.status === 404) {
        $('#domain-info').text('Ce domaine n\'est pas encore connu');
      } else {
        $('#domain-info').text('Échec de la vérification');
      }
    });
  }

  function generateWikidoc() {
    var wikidoc = '';
    if ($('#titre').val()) { wikidoc += "==== " + $('#titre').val() + " ====\n"; }
    wikidoc += "^URL|" + ($('#url').val() || ' ') + "|\n";
    wikidoc += "^Donne accès à|" + ($('#acces').val() || ' ') + "|\n";
    wikidoc += "^Host|" + ($('#host').val() || ' ') + "|\n";
    wikidoc += "^Path|" + ($('#fullpath').val() || ' ') + "|\n";
    wikidoc += "^:::^Décomposition du path^\n";

    $('.pathparam').each(function () {
      if ($(this).val()) { wikidoc += '^:::|' + $(this).val() + '|\n'; }
    });

    wikidoc += "^Query|" + ($('#fullquery').val() || ' ') + "|\n";
    wikidoc += "^:::^Décomposition de la query^\n";

    $('.queryparam').each(function () {
      if ($(this).val()) { wikidoc += '^:::|' + $(this).val() + '|\n'; }
    });

    wikidoc += "^[[http://analogist.couperin.org/ezpaarse/doc/glossaire#identifiants-de-ressources|Identifiant(s) de ressource]]|" + ($('#identifier').val() || ' ') + "|\n";
    wikidoc += "^[[http://analogist.couperin.org/ezpaarse/doc/glossaire#types-de-ressources|Type de ressource]]|" + ($('#rtype').val() || ' ') + "|\n";
    wikidoc += "^[[http://analogist.couperin.org/ezpaarse/doc/glossaire#formats-de-ressources|Format]]|" + ($('#mime').val() || ' ') + "|\n";
    wikidoc += "^[[http://analogist.couperin.org/ezpaarse/doc/glossaire#description-de-unitid|UnitID]]|" + ($('#unitid').val() || ' ') + "|\n";

    if ($('#remarque').val()) { wikidoc += "=== Remarque ===\n" + $('#remarque').val(); }

    $('#wikidoc').val(wikidoc);
    saveForm();
  }

  function loadForm() {
    if (!localStorage['form']) { return; }

    var form;
    try {
      form = JSON.parse(localStorage['form']);
    } catch (e) { return; }

    if (form.fields) {
      form.fields.forEach(function (field) {
        $('#' + field.id).val(field.value);
      });
    }

    checkDomain();

    if (form.queryparams) {
      form.queryparams.forEach(function (value) {
        var input = $('<input>', {
          type: 'text',
          class: 'queryparam',
          placeholder: 'Paramètre de la query',
          value: value
        });
        $('#queryparams').append(input);
      });
      checkQueryParams();
    }
    if (form.pathparams) {
      form.pathparams.forEach(function (value) {
        var input = $('<input>', {
          type: 'text',
          class: 'pathparam',
          placeholder: 'Paramètre du path',
          value: value
        });
        $('#pathparams').append(input);
      });
      checkPathParams();
    }
  }

  function saveForm() {
    var form = {
      queryparams: [],
      pathparams: [],
      fields: []
    };

    $('input[id], select, textarea').each(function (i, el) {
      if (el.value) { form.fields.push({ id: el.id, value: el.value }); }
    });

    $('#queryparams input').each(function (i, el) {
      if (el.value) { form.queryparams.push(el.value); }
    });
    $('#pathparams input').each(function (i, el) {
      if (el.value) { form.pathparams.push(el.value); }
    });

    localStorage['form'] = JSON.stringify(form);
  }

  $('#reset').click(function reset() {
    delete localStorage['form'];
    $('input, select, textarea').each(function () { $(this).val(''); });
    checkPathParams();
    checkQueryParams();
    checkDomain();
  });
});