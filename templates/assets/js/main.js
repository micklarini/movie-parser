import 'bootstrap';
import 'datatables.net';
import dt from 'datatables.net-bs4';

'use strict';

$(function() {

  $(document).on('click', 'a.modal-link', function(e) {
    $('#sw-modal').modal();
    return false;
  });

  let $rateTable = $('#rate-table');

  $rateTable.DataTable({
    autoWidth: false,
    paging: true,
    pagingType: 'simple',
    searching: false,
    language: { url: "/js/datatables-ru.json" },
    stateSave: true,
    processing: true,
    serverSide: true,
    ajax: {
      url: $rateTable.data('source'),
      dataSrc: 'data',
    },
    columns: [
      { data: "id", visible: false },
      { data: "position", title: "№", type: "num", className: "column-position" },
      { data: "image", title: "Обложка", className: "column-image" },
      { data: "title", title: "Название", className: "column-title" },
      { data: "prod_year", title: "Год", type: "num", className: "column-year" },
      { data: "country_id", visible: false },
      { data: "country" , title: "Страна", className: "column-country" },
      { data: "rate", title: "Расчётный<br/>балл", type: "num", className: "column-rate" },
      { data: "votes", title: "Голосов", type: "num", className: "column-votes" },
      { data: "rate_calc", title: "Средний<br/>балл", type: "num", className: "column-rate_calc" },
    ],
    order: [[1, 'asc']],
  });
});
