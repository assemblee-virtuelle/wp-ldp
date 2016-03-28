jQuery( document ).ready(function($) {
  $('input:radio[name="tax_input[ldp_container][]"]').click(function() {
      //TODO: Switch to a smooth AJAX call for changing the container, instead of reloading the page.
      // var value = $(this).val();
      // console.log('Radio button selected value: ', value);
      // console.log(store);
      // var firstLabel = $('#ldp_container-' + value).find('label')[0];
      // var newContainerName = firstLabel.innerText.toLowerCase();
      //
      // console.log(newContainerName);
      // store.render('#ldpform', containerName, undefined, undefined, newContainerName, 'ldp_');

      var form = $('#post');
      form.submit();
  });

  $('input').keypress(function(event) {
      if (event.which == 13) {
          event.preventDefault();
          $('#post').submit();
      }
  });
});
