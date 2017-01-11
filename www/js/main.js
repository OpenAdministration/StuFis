
$(document).ready(function() {
    $('.selectpicker').selectpicker({
        style: 'btn-default',
        size: false
    });

    $('.column-sum').each(function (i, e) {
       var $e = $(e);
       var colId = $e.data('col-id');
       $e.addClass(colId);
       $('.'+colId+' input').each(function() {
         $(this).on('change.column-sum', null, colId, function (evt) {
            var val= parseFloat($(this).val());
            $(this).val(val.toFixed(2));
           updateColumnSum(evt.data);
         });
       });
       updateColumnSum(colId);
    });

    $('.dynamic-table tbody').each(function (i, tbody) {
     $(tbody).find('tr.new-table-row').each(function(i, tr) {
       $(tr).find("*").on('focus.dynamic-table', null, {"table": $(tbody), "tr": $(tr)}, function (evt) {
         alert('focus');
         var $tr = evt.data.tr;
         var $ntr = $tr.clone(true);
         $tr.find("*").off('focus.dynamic-table');
         $ntr.appendAfter($tr);
/* trigger clone, fixup id attr, fixup row numbering */
       });
     });
   });
});

function updateColumnSum(colId) {
  var $e = $('.column-sum.'+colId);
  var sum = 0;
  $('.'+colId+' input').each(function() {
    sum += parseFloat($(this).val());
  });
  $e.text("Σ " + sum.toFixed(2) + " €");
}

