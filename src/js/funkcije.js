$(function () {
  $(document).on('keydown', 'form', function (event) {
    if (event.key !== 'Enter') {
      return;
    }

    const target = event.target;
    const $target = $(target);
    const tagName = target.tagName ? target.tagName.toLowerCase() : '';
    const typeAttr = (target.getAttribute && target.getAttribute('type')) || '';

    if (
      tagName === 'textarea' ||
      target.isContentEditable ||
      typeAttr.toLowerCase() === 'submit' ||
      typeAttr.toLowerCase() === 'button' ||
      typeAttr.toLowerCase() === 'reset' ||
      $target.attr('data-allow-enter') === 'true'
    ) {
      return;
    }

    event.preventDefault();
  });

  const pathName = (window.location.pathname || '').toLowerCase();
  if (pathName.indexOf('izmena_') !== -1) {
    const trackedForms = $('form').filter(function () {
      return $(this).attr('data-track-unsaved') !== 'false';
    });

    if (trackedForms.length) {
      const dirtyForms = new Set();

      const recalcDirtyState = function ($form) {
        const initialState = $form.data('initial-serialized') || '';
        const currentState = $form.serialize();

        if (currentState !== initialState) {
          dirtyForms.add($form[0]);
        } else {
          dirtyForms.delete($form[0]);
        }
      };

      const refreshInitialState = function ($form) {
        $form.data('initial-serialized', $form.serialize());
        dirtyForms.delete($form[0]);
      };

      trackedForms.each(function () {
        const $form = $(this);
        refreshInitialState($form);

        const inputs = $form.find('input:not([type="hidden"]):not([data-track-unsaved="false"]), select:not([data-track-unsaved="false"]), textarea:not([data-track-unsaved="false"])');

        const handlePotentialChange = function () {
          recalcDirtyState($form);
        };

        inputs.on('input change', handlePotentialChange);

        $form.on('submit', function () {
          refreshInitialState($form);
        });

        $form.on('reset', function () {
          setTimeout(function () {
            refreshInitialState($form);
          }, 0);
        });

        $form.on('change', function () {
          recalcDirtyState($form);
        });
      });

      const hasDirtyForms = function () {
        return dirtyForms.size > 0;
      };

      window.unsavedChanges = window.unsavedChanges || {};
      window.unsavedChanges.hasChanges = hasDirtyForms;
      window.unsavedChanges.markDirty = function () {
        trackedForms.each(function () {
          dirtyForms.add(this);
        });
      };
      window.unsavedChanges.markPristine = function () {
        trackedForms.each(function () {
          refreshInitialState($(this));
        });
      };

      window.addEventListener('beforeunload', function (event) {
        if (!hasDirtyForms()) {
          return;
        }

        const message = 'Imate nesačuvane izmene. Ako napustite stranicu, promene će biti izgubljene.';
        event.preventDefault();
        event.returnValue = message;
        return message;
      });
    }
  }
});

function back() {
  event.preventDefault();
  history.go(-1);
}

function logovanje() {
  window.location.replace('../index.php');
}

function unesi_objekat() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '' || values.broj_stanova == '' || values.broj_lokala == ''|| values.broj_garaza == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  // Dodaj faze direktno iz DOM elemenata ako nisu u values
  if (!values.faza_1) values.faza_1 = document.getElementById('faza_1')?.value || '';
  if (!values.faza_2) values.faza_2 = document.getElementById('faza_2')?.value || '';
  if (!values.faza_3) values.faza_3 = document.getElementById('faza_3')?.value || '';
  if (!values.faza_4) values.faza_4 = document.getElementById('faza_4')?.value || '';

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_objekat.php',
    data: {
      naziv: values.naziv,
      broj_stanova: values.broj_stanova,
      broj_lokala: values.broj_lokala,
      broj_garaza: values.broj_garaza,
      broj_parkinga : values.broj_parkinga,
      faza_1: values.faza_1,
      faza_2: values.faza_2,
      faza_3: values.faza_3,
      faza_4: values.faza_4,
    },
    success: function () {
      alert('Objekat uspešno kreiran');
    },
    error: function (xhr) {
      if (xhr.status === 400) {
        try {
          var response = JSON.parse(xhr.responseText);
          alert('Greška: ' + response.error);
        } catch (e) {
          alert('Objekat već postoji ili nisu uneti svi podatci');
        }
      } else {
        alert('Objekat već postoji ili nisu uneti svi podatci');
      }
    },
  }).done(function () {
    window.location.replace('../php/glavni.php');
  });
}

function izmeni_gradiliste() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_gradiliste.php',
    data: {
      id: values.id,
      naziv: values.naziv,
      tip: values.tip,
      stanje: values.stanje,
      grad: values.grad,
    },
    success: function () {
      alert('Gradilište uspešno izmenjeno');
    },
    error: function () {
      alert('Gradilište nije izmenjeno');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function unesi_tip_troska() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_tip_troska.php',
    data: {
      naziv: values.naziv,
      tip: values.tip,
    },
    success: function () {
      alert('Tip troška uspešno kreiran');
    },
    error: function () {
      alert('Tip troška već postoji ili nisu uneti svi podatci');
    },
  }).done(function () {
    window.location.replace('../php/glavni.php');
  });
}

function izmeni_tip_troska() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_tip_troska.php',
    data: {
      id: values.id,
      naziv: values.naziv,
      tip: values.tip,
    },
    success: function () {
      alert('Tip troška uspešno izmenjen');
    },
    error: function () {
      alert('Tip troška nije izmenjen');
    },
  }).done(function (html) {
    $('#results').html(html);
    window.location.href = "izmena_tip_troska_lista.php";
  });
}

function unesi_o_tip_troska() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_o_tip_troska.php',
    data: {
      naziv: values.naziv,
    },
    success: function () {
      alert('Tip troška uspešno kreiran');
    },
    error: function () {
      alert('Tip troška već postoji ili nisu uneti svi podatci');
    },
  }).done(function () {
    window.location.replace('../php/glavni.php');
  });
}

function izmeni_o_tip_troska() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_o_tip_troska.php',
    data: {
      id: values.id,
      naziv: values.naziv,
    },
    success: function () {
      alert('Tip troška uspešno izmenjen');
    },
    error: function () {
      alert('Tip troška nije izmenjen');
    },
  }).done(function (html) {
    $('#results').html(html);
    window.location.href = "izmena_o_tip_troska_lista.php";
  });
}

function unesi_kupca() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (values.mesto_uplate == 'Izaberite gradilište') {
    alert('Nisu uneti svi podatci');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_kupca.php',
    data: {
      naziv: values.naziv,
      tip: values.mesto_uplate,  //namerno
    },
    success: function () {
      alert('Kupac je uspešno kreiran');
    },
    error: function () {
      alert('Kupac već postoji ili nisu uneti svi podatci');
    },
  }).done(function () {
    window.location.replace('../php/glavni.php');
  });
}

function izmeni_kupca() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_kupca.php',
    data: {
      id: values.id,
      naziv: values.naziv,
      tip: values.mesto_uplate, //namerno
    },
    success: function () {
      alert('Kupac uspešno izmenjen');
    },
    error: function () {
      alert('Kupac nije izmenjen');
    },
  }).done(function (html) {
    $('#results').html(html);
    window.location.href = "izmena_kupca_lista.php";
  });
}

function izmeni_kurs() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.euro_val == '') {
    alert('Morate uneti kurs');
    return;
  }

  if (!Number.isInteger(parseInt(values.euro_val))) {
    alert('Iznos nije broj');
    return;
  }

  values.euro_val = values.euro_val.replace(/,/g, '.');

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_kurs.php',
    data: {
      euro_val: values.euro_val,
    },
    success: function () {
      alert('Kurs uspešno izmenjen, za vraćanje na današnji kurs izađite iz programa');
    },
    error: function () {
      alert('Kurs nije izmenjen');
    },
  }).done(function (html) {
    $('#results').html(html);
    window.location.href = "glavni.php";
  });
}

function unesi_racun() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv === '' || values.stanje === '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: 'unesi_racun.php',
    data: {
      naziv: values.naziv,
      stanje: values.stanje,
    },
    success: function () {
      alert('Račun uspešno kreiran');
    },
    error: function () {
      alert('Račun već postoji ili nisu uneti svi podatci');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function izmeni_racun() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  $.ajax({
    type: 'POST',
    url: 'izmeni_racun.php',
    data: {
      id: values.id,
      naziv: values.naziv,
      stanje: values.stanje,
    },
    success: function () {
      alert('Račun uspešno izmenjen');
    },
    error: function () {
      alert('Račun nije izmenjen');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function unesi_trosak() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos == '') {
    alert('Nisu uneti svi podatci');
    return;
  }


  if (values.mesto_uplate == 'Izaberite gradilište') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (!Number.isInteger(parseInt(values.iznos))) {
    alert('Iznos nije broj');
    return;
  }

  values.dinari = "0";
  values.placeno = "1";
  values.pdv = "0";
  values.iznos = values.iznos.replace(/,/g, '.');

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }

  if (document.getElementById('pdv').checked) {
    values.pdv = "1";
  } else {
    values.pdv = "0";
  }

  if (document.getElementById('placeno').checked) {
    values.placeno = "1";
  } else {
    values.placeno = "0";
  }



  console.log(values);
  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_trosak.php',
    data: {
      tip_troska: values.tip_troska,
      mesto_uplate: values.mesto_uplate,
      iznos: values.iznos,
      dinari: values.dinari,
      pdv: values.pdv,
      placeno: values.placeno,
      komentar: values.komentar,
      pdv_datum: values.pdv_datum,
    },
    success: function () {
      alert('Trošak uspešno unesen');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function unesi_priliv() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (!Number.isInteger(parseInt(values.iznos))) {
    alert('Iznos nije broj');
    return;
  }

  values.dinari = "0";
  values.placeno = "1";
  values.iznos = values.iznos.replace(/,/g, '.');

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }

  if (document.getElementById('placeno').checked) {
    values.placeno = "1";
  } else {
    values.placeno = "0";
  }



  console.log(values);
  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_uplate.php',
    data: {
      tip_uplate: values.tip_uplate,
      mesto_uplate: values.mesto_uplate,
      iznos: values.iznos,
      dinari: values.dinari,
      pdv: 0,
      placeno: values.placeno,
      komentar: values.komentar,
      kupac: values.kupac,
    },
    success: function () {
      alert('Priliv uspešno unesen');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}


function unesi_o_priliv() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (!Number.isInteger(parseInt(values.iznos))) {
    alert('Iznos nije broj');
    return;
  }

  values.dinari = "0";
  values.placeno = "1";
  values.iznos = values.iznos.replace(/,/g, '.');

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }

  if (document.getElementById('placeno').checked) {
    values.placeno = "1";
  } else {
    values.placeno = "0";
  }



  console.log(values);
  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_ostale_uplate.php',
    data: {
      tip_uplate: values.tip_uplate,
      mesto_uplate: values.mesto_uplate,
      iznos: values.iznos,
      dinari: values.dinari,
      pdv: 0,
      placeno: values.placeno,
      komentar: values.komentar,
    },
    success: function () {
      alert('Priliv uspešno unesen');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}



function period_izvestaj() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  var vreme_od = $('#vreme_od').val();
  var vreme_do = $('#vreme_do').val();

  values.vreme_od = vreme_od;
  values.vreme_do = vreme_do;

  if (values.vreme_od == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (values.vreme_do == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (values.firma == '0') {
    console.log('firma nula');
  }

  if (values.gradiliste == '0') {
    console.log('racun nula');
  }

  console.log(values);

  console.log(
    'izvestaj_po_periodu_get.php?datumod=' +
    values.vreme_od +
    '&datumdo=' +
    values.vreme_do +
    '&tip=' +
    values.tip +
    '&firma=' +
    values.firma +
    '&gradiliste=' +
    values.gradiliste
  );

  let get_url =
    'izvestaj_po_periodu_get.php?datumod=' +
    values.vreme_od +
    '&datumdo=' +
    values.vreme_do +
    '&tip=' +
    values.tip +
    '&firma=' +
    values.firma +
    '&gradiliste=' +
    values.gradiliste

  window.location.href = get_url;
}

function kupac_izvestaj() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });


  if (values.gradiliste == '0') {
    console.log('racun nula');
  }
  console.log(values);

  let get_url =
    'izvestaj_kupci.php?gradiliste=' +
    values.gradiliste;

  window.location.href = get_url;
}


function troskovi_mesec_izvestaj() {
  event.preventDefault();
  var gradiliste = $('#gradiliste').val();

  // Prikupi sve čekirane mesece
  var meseci = [];
  $('input[name="mesec[]"]:checked').each(function () {
    meseci.push($(this).val());
  });

  // Prikupi sve čekirane godine
  var godine = [];
  $('input[name="godina[]"]:checked').each(function () {
    godine.push($(this).val());
  });

  if (gradiliste == '0') {
    alert('Morate izabrati gradilište');
    return;
  }

  // Formiraj query string za više meseci i godina
  var params = 'gradiliste=' + encodeURIComponent(gradiliste);

  meseci.forEach(function (m) {
    params += '&mesec[]=' + encodeURIComponent(m);
  });
  godine.forEach(function (g) {
    params += '&godina[]=' + encodeURIComponent(g);
  });

  let get_url = 'izvestaj_troskovi_mesec.php?' + params;
  window.location.href = get_url;
}


function prihodi_mesec_izvestaj() {
  event.preventDefault();
  var gradiliste = $('#gradiliste').val();

  // Prikupi sve čekirane mesece
  var meseci = [];
  $('input[name="mesec[]"]:checked').each(function () {
    meseci.push($(this).val());
  });

  // Prikupi sve čekirane godine
  var godine = [];
  $('input[name="godina[]"]:checked').each(function () {
    godine.push($(this).val());
  });

  if (gradiliste == '0') {
    alert('Morate izabrati gradilište');
    return;
  }

  // Formiraj query string za više meseci i godina
  var params = 'gradiliste=' + encodeURIComponent(gradiliste);

  meseci.forEach(function (m) {
    params += '&mesec[]=' + encodeURIComponent(m);
  });
  godine.forEach(function (g) {
    params += '&godina[]=' + encodeURIComponent(g);
  });

  let get_url = 'izvestaj_prihodi_mesec.php?' + params;
  window.location.href = get_url;
}


function tableToExcel(p) {
  var dlink = document.createElement('a');
  document.body.appendChild(dlink);
  var uri = 'data:application/vnd.ms-excel;base64,';
  var template =
    '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"></head><body><table>{table}</table></body></html>';
  var base64 = function (s) {
    return window.btoa(unescape(encodeURIComponent(s)));
  };
  var format = function (s, c) {
    return s.replace(/{(\w+)}/g, function (m, p) {
      return c[p];
    });
  };
  if (!p.table.nodeType) p.table = document.getElementById(p.table);
  var ctx = { worksheet: p.worksheet || 'Worksheet', table: p.table.innerHTML };
  dlink.href = uri + base64(format(template, ctx));
  dlink.download = p.filename;
  dlink.click();
  document.body.removeChild(dlink);
}

function unesi_tip() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: 'unesi_tip.php',
    data: {
      naziv: values.naziv,
    },
    success: function () {
      alert('Tip troška uspešno kreiran');
    },
    error: function () {
      alert('Tip troška već postoji !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function unesi_mesto() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '' || values.tip == '' || values.stanje == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: 'unesi_mesto.php',
    data: {
      naziv: values.naziv,
      tip: values.tip,
      pod_tip: values.pod_tip,
      stanje: values.stanje,
    },
    success: function () {
      alert('Mesto troška uspešno kreirano');
    },
    error: function () {
      alert('Mesto troška već postoji !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}

function mesto_izvestaj() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  console.log(
    'izvestaj_po_mestu_get.php?datumod=' +
    '&tip=' +
    values.tip +
    '&racun=' +
    values.racun +
    '&mesto=' +
    values.mesto
  );

  let get_url =
    'izvestaj_po_mestu_get.php?datumod=' +
    '&tip=' +
    values.tip +
    '&racun=' +
    values.racun +
    '&mesto=' +
    values.mesto;

  window.location.href = get_url;
}

function unesi_avans() {
  event.preventDefault();
  var selektovaniradnici = $('#radnik option:selected').toArray().map(item => item.value).join();
  var radnici = selektovaniradnici.split(',');

  var values = {};

  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos == '') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (!Number.isInteger(parseInt(values.iznos))) {
    alert('Iznos nije broj');
    return;
  }

  values.dinari = "0";
  values.iznos = values.iznos.replace(/,/g, '.');

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }


  console.log(values);

  $.ajax({
    type: 'POST',
    url: 'unos_registar_avans.php',
    data: {
      radnik: radnici,
      iznos: values.iznos,
      dinari: values.dinari,
    },
    success: function () {
      console.log(radnici);

      alert('Avans uspešno unesen');
    },
    error: function () {
      alert('Unos avansa nije uspeo !');
    },
  }).done(function (html) {
    window.location.replace('../php/glavni.php');
    //$('#results').html(html);
  });
}

function unesi_sate() {
  event.preventDefault();
  var selektovaniradnici = $('#radnik option:selected').toArray().map(item => item.value).join();
  var radnici = selektovaniradnici.split(',');
  console.log(radnici);
  var values = {};

  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.sati == '') {
    alert('Nisu uneti svi podatci');
    return;
  }


  if (!Number.isInteger(parseInt(values.sati))) {
    alert('Sati moraju biti broj');
    return;
  }



  if (values.mesto_uplate == 0) {
    alert('Morate izabrati gradilište');
    return;
  }

  values.sati = values.sati.replace(/,/g, '.');


  console.log(values);
  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_plata.php',
    data: {
      radnik: radnici,
      sati: values.sati,
      datum: values.datum,
      mesto_uplate: values.mesto_uplate,
    },
    success: function () {
      alert('Uspešno ste uneli sate');
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });

}


function izaberi_mesec() {
  event.preventDefault();
  var mesec = $('#datum').val();
  if (mesec == null) {
    alert('Morate izabrati mesec');
    return;
  }
  window.location.href = "NS_karnet.php?mesec=" + mesec;
}

function izaberi_mesec_pl() {
  event.preventDefault();
  var mesec = $('#datum').val();
  if (mesec == null) {
    alert('Morate izabrati mesec');
    return;
  }
  window.location.href = "NS_karnet_pl.php?mesec=" + mesec;
}



function izaberi_mesec_NIS() {
  event.preventDefault();
  var mesec = $('#datum').val();
  if (mesec == null) {
    alert('Morate izabrati mesec');
    return;
  }
  window.location.href = "NIS_karnet.php?mesec=" + mesec;
}


function izaberi_mesec_pl_NIS() {
  event.preventDefault();
  var mesec = $('#datum').val();
  if (mesec == null) {
    alert('Morate izabrati mesec');
    return;
  }
  window.location.href = "NIS_karnet_pl.php?mesec=" + mesec;
}

function izmeni_radnika() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (
    values.ime == '' ||
    values.prezime == '' ||
    values.satnica == ''
  ) {
    alert('Morate uneti sve podatke!');
    return;
  }
  values.id = parseInt(values.id);
  values.satnica = values.satnica.replace(/,/g, '.');
  values.stanje = values.stanje.replace(/,/g, '.');

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_radnika.php',
    data: {
      id: values.id,
      ime: values.ime,
      prezime: values.prezime,
      opis: values.opis,
      satnica: values.satnica,
      grad: values.grad,
      aktivan: values.aktivan,
      stanje: values.stanje,
      obrok: values.obrok,
    },
    success: function () {
      console.log(values.id, values.ime, values.satnica, values.grad, values.aktivan, values.stanje);
      alert('Korsinik uspešno izmenjen');
    },
    error: function () {
      alert('Korisnik nije izmenjen');
    },
  }).done(function () {
    window.location.replace('../php/izmena_radnika.php');
  });
}


function izmeni_transakciju() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  var datum_rastav_dan = values.datum.substring(0, 2);
  var datum_rastav_mesec = values.datum.substring(3, 5);
  var datum_rastav_godina = values.datum.substring(values.datum.length - 4);

  values.datum = datum_rastav_godina + "-" + datum_rastav_mesec + "-" + datum_rastav_dan;
  if (
    values.iznos == ''
  ) {
    alert('Morate uneti sve podatke!');
    return;
  }

  alert(values.datum);
  values.id = parseInt(values.id);
  values.iznos = values.iznos.replace(/,/g, '.');


  $.ajax({
    type: 'POST',
    url: 'izmeni_transkaciju.php',
    data: {
      id: values.id,
      mesto_uplate: values.gradiliste,
      datum: values.datum,
      iznos: values.iznos,
      komentar: values.komentar,
    },
    success: function () {
      console.log(values);
      alert('Transakcija uspešno izmenjena');
    },
    error: function () {
      alert('Transakcija nije izmenjena');
    },
  }).done(function () {
    window.location.replace('../php/transakcije_katalog.php');
  });
}


function unesi_trosak_ostalo() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos == '') {
    alert('Nisu uneti svi podatci');
    return;
  }
  if (values.tip_o_troska == 'Izaberite tip') {
    alert('Nisu uneti svi podatci');
    return;
  }

  if (!Number.isInteger(parseInt(values.iznos))) {
    alert('Iznos nije broj');
    return;
  }

  values.dinari = "0";
  values.placeno = "1";
  values.pdv = "0";
  values.iznos = values.iznos.replace(/,/g, '.');

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }

  if (document.getElementById('pdv').checked) {
    values.pdv = "1";
  } else {
    values.pdv = "0";
  }

  if (document.getElementById('placeno').checked) {
    values.placeno = "1";
  } else {
    values.placeno = "0";
  }


  console.log(values);
  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_ostali_trosak.php',
    data: {
      tip_o_troska: values.tip_o_troska,
      vozilo: values.vozilo,
      iznos: values.iznos,
      dinari: values.dinari,
      pdv: values.pdv,
      placeno: values.placeno,
      komentar: values.komentar,
      pdv_datum: values.pdv_datum,
    },
    success: function () {
      alert('Trošak uspešno unesen');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });
}


function unesi_radnika() {
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });
  values.stanje = values.stanje.replace(/,/g, '.');
  values.satnica = values.satnica.replace(/,/g, '.');
  console.log(values);

  if (values.ime === '' || values.prezime === '' || values.satnica === '' || values.stanje === '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_radnika.php',
    data: {
      ime: values.ime,
      prezime: values.prezime,
      opis: values.opis,
      satnica: values.satnica,
      grad: values.grad,
      aktivan: values.aktivan,
      obrok: values.obrok,
      stanje: values.stanje,
    },
    success: function () {
      alert('Radnik uspešno kreiran');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Radnik nije uspešno kreiran!');
    },
  }).done(function () {
    window.location.replace('../php/izmena_radnika.php');
  });
}


function unesi_avanse() {
  event.preventDefault();
  var selektovaniradnici = $('#radnik option:selected').toArray().map(item => item.value).join();
  var radnici = selektovaniradnici.split(',');
  console.log(radnici);
  var values = {};

  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  if (values.iznos_avansa == '') {
    alert('Nisu uneti svi podatci');
    return;
  }


  if (!Number.isInteger(parseInt(values.iznos_avansa))) {
    alert('Iznos moraju biti broj');
    return;
  }

  if (document.getElementById('dinari').checked) {
    values.dinari = "1";
  } else {
    values.dinari = "0";
  }


  values.iznos_avansa = values.iznos_avansa.replace(/,/g, '.');


  console.log(values);

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unos_avansa.php',
    data: {
      radnik: radnici,
      iznos_avansa: values.iznos_avansa,
      mesec: values.mesec,
      tip_avansa: values.isplata_tip,
      dinari: values.dinari,
    },
    success: function () {
      alert('Uspešno ste uneli avans');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Unos nije uspeo !');
    },
  }).done(function (html) {
    $('#results').html(html);
  });

}


function unesi_vozilo() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  console.log(values);

  if (values.naziv == '' || values.stanje == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_vozilo.php',
    data: {
      naziv: values.naziv,
      stanje: values.stanje,
      registracija: values.registracija,
      grad: values.grad,
    },
    success: function () {
      alert('Vozilo uspešno kreirano');
      $('#forma')[0].reset();
    },
    error: function () {
      alert('Vozilo već postoji ili nisu uneti svi podatci');
    },
  }).done(function () {
    window.location.replace('../php/glavni.php');
  });
}

function izmeni_vozilo() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  values.stanje = values.stanje.replace(/,/g, '.');

  console.log(values);

  if (values.naziv == '' || values.stanje == '') {
    alert('Morate uneti sve podatke');
    return;
  }

  $.ajax({
    type: 'POST',
    url: './funkcije/f_izmeni_vozilo.php',
    data: {
      id: values.id,
      naziv: values.naziv,
      stanje: values.stanje,
      registracija: values.registracija,
      grad: values.grad,
    },
    success: function () {
      alert('Vozilo uspešno izmenjeno');
    },
    error: function () {
      alert('Vozilo već postoji ili nisu uneti svi podatci');
    },
  }).done(function () {
    window.location.replace('../php/izmena_vozila_lista.php');
  });
}

function snimi_plate_iz_karneta(mesec) {

  $.ajax({
    type: 'POST',
    url: './funkcije/f_snimi_plate_iz_karneta.php',
    data: {
      mesec: mesec,
    },
    success: function () {
      alert('Snimio uplate za mesec ' + mesec);
    },
    error: function () {
      alert('Greška, nisu snimljene promene');
    },
  }).done(function (html) {
    $('#results').html(html);
  });

}


function unesi_racun_stanje() {
  event.preventDefault();
  var values = {};
  $.each($('#forma').serializeArray(), function (i, field) {
    values[field.name] = field.value;
  });

  values.iznos_nlb = values.iznos_nlb.replace(/\s+/g, '');
  values.iznos_nlb = values.iznos_nlb.replace(/,/g, '.');

  values.iznos_inteza_raz = values.iznos_inteza_raz.replace(/\s+/g, '');
  values.iznos_inteza_raz = values.iznos_inteza_raz.replace(/,/g, '.');

  values.iznos_inteza_raz_invest = values.iznos_inteza_raz_invest.replace(/\s+/g, '');
  values.iznos_inteza_raz_invest = values.iznos_inteza_raz_invest.replace(/,/g, '.');

  values.iznos_unicredit_raz = values.iznos_unicredit_raz.replace(/\s+/g, '');
  values.iznos_unicredit_raz = values.iznos_unicredit_raz.replace(/,/g, '.');

  values.iznos_inteza_opk = values.iznos_inteza_opk.replace(/\s+/g, '');
  values.iznos_inteza_opk = values.iznos_inteza_opk.replace(/,/g, '.');

  values.iznos_unicredit_opk = values.iznos_unicredit_opk.replace(/\s+/g, '');
  values.iznos_unicredit_opk = values.iznos_unicredit_opk.replace(/,/g, '.');

  values.iznos_unicredit_raz_invest = values.iznos_unicredit_raz_invest.replace(/\s+/g, '');
  values.iznos_unicredit_raz_invest = values.iznos_unicredit_raz_invest.replace(/,/g, '.');

  values.iznos_gotovina = values.iznos_gotovina.replace(/\s+/g, '');
  values.iznos_gotovina = values.iznos_gotovina.replace(/,/g, '.');

  values.iznos_bolovanja = values.iznos_bolovanja.replace(/\s+/g, '');
  values.iznos_bolovanja = values.iznos_bolovanja.replace(/,/g, '.');

  $.ajax({
    type: 'POST',
    url: './funkcije/f_unesi_racun.php',
    data: {
      iznos_nlb: values.iznos_nlb,
      iznos_inteza_raz: values.iznos_inteza_raz,
      iznos_inteza_raz_invest: values.iznos_inteza_raz_invest,
      iznos_unicredit_raz: values.iznos_unicredit_raz,
      iznos_unicredit_raz_invest: values.iznos_unicredit_raz_invest,
      iznos_gotovina: values.iznos_gotovina,
      iznos_bolovanja: values.iznos_bolovanja,
      iznos_inteza_opk: values.iznos_inteza_opk,
      iznos_unicredit_opk: values.iznos_unicredit_opk,
    },
    success: function () {
      alert('Snimio stanje računa');
    },
    error: function () {
      alert('Greška, nisu snimljene promene');
    },
  }).done(function (html) {
    location.reload();
    //$('#results').html(html);
  });

}