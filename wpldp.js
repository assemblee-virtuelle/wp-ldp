window.wpldp = function( store, options ) {

    this.options = options || {};
    this.store   = store;
    this.resultSet = [];
    Handlebars.logger.level = 0;
    this.current_site_url = site_rest_url + 'ldp/v1/sites/';

    this.init = function() {
      jQuery('input:radio[name="tax_input[ldp_container][]"]').click(function() {
          //TODO: Switch to a smooth AJAX call for changing the container, instead of reloading the page.
          var form = jQuery('#post');
          form.submit();
      });

      jQuery('input').keypress(function(event) {
          if (event.which == 13) {
              event.preventDefault();
              jQuery('#post').submit();
          }
      });

      this.bindEvents();

      this.loadData();
   }

   this.loadData = function () {
       var instance = this;
       this.store.list( instance.current_site_url ).then( function( sites ) {
           sites.forEach( function( site ) {
               this.store.list( site['@id'] ).then( function( containers ) {
                   console.log('TOTOT');
                   containers.forEach( function( container ) {
                       console.log('Contianer', container );
                       this.store.list( container['@id'] ).then( function( resources ) {
                           console.log( 'Resources', resources );
                           resources.forEach( function( resource ) {
                               instance.resultSet.push( resource );
                               console.log( instance.resultSet );
                           });
                       });
                   });
               });
           });
       });
   }

    this.bindEvents = function() {
        var instance = this;
        jQuery('.remove-field-button').click( function( event ) {
            instance.removeField( event );
        });

        jQuery('input[type=date]').datepicker();

        var topics = [];
        jQuery("#ldpform").on('focus', '.ldpLookup', function(event) {
            jQuery(this).autocomplete({
                autoFocus: true,
                minlength: 3,
                search: function() {
                    jQuery(this).addClass('sf-suggestion-search')
                },
                open: function() {
                    jQuery(this).removeClass('sf-suggestion-search')
                },
                select: function( event, ui ) {
                    var emptyFields = jQuery(this).siblings().filter(function(index) { return jQuery(this).val() == ''}).length;
                },
                source: function(request, callback) {
                    var searchResults = []
                    instance.resultSet.forEach( function( result ) {
                        if ( result['@type'] == event.target.parentNode.dataset.range ) {
                            searchResults.push( { 'label': result['foaf:name'], 'value': result['@id'] } );
                        }
                    });
                    callback( searchResults );
                }
            });
        });
    }

     this.removeField = function removeField( event ) {
         event.preventDefault();
         event.stopPropagation();

         var target_id = event.target.id.substring('remove-field-'.length);
         var target_div = document.getElementById( target_id );

         target_div.parentNode.removeChild( target_div );
         event.target.parentNode.removeChild( event.target );
         return false;
     }

    this.render = function render( div, objectIri, template, context, modelName, prefix ) {
        var objectIri = this.store.getIri(objectIri);
        var template = template ? template : this.store.mainTemplate;
        var context = context || this.store.context;
        var fields = modelName ? this.store.models[modelName].fields : null;
        var instance = this;

        this.store.get(objectIri).then(function(object) {
            if (fields) {
              fields.forEach( function(field) {
                if (field.name) {
                  var propertyName = field.name;
                } else if (field['data-property']) {
                  field.name = field['data-property'];
                }  else if (field['object-property']) {
                  field.name = field['object-property'];
                }

                if (prefix) {
                  propertyName = propertyName.replace(prefix, '');
                }

                if ( field.multiple == "true" ) {
                  if (object[field.name]) {
                    if ( Array.isArray(object[field.name])) {
                        field.fields = object[field.name];
                    } else {
                        field.fields = [ object[field.name] ];
                    }
                  } else {
                    field.fields = new Array();
                  }
                } else {
                  field.fieldValue = object[field.name];
                }
              });
            }
           if (typeof(template) == 'string' && template.substring(0, 1) == '#') {
             var element = jQuery(template);
             if (element && typeof element.attr('src') !== 'undefined') {
               instance.getTemplateAjax(element.attr('src'), function(template) {
                 jQuery(div).html(template({object: object}));
               });
             } else {
               template = Handlebars.compile(element.html());
               jQuery(div).html(template({object: object}));
             }
           } else {
             template = Handlebars.compile(template);
             jQuery(div).html(template({object: object}));
           }

           instance.bindEvents();
        });
    }

     // Get handlebars templates via ajax
    this.getTemplateAjax = function getTemplateAjax( path, callback ) {
       var source, template;
       jQuery.ajax({
           url: path,
           success: function (data) {
               source = data;
               template = Handlebars.compile(source);
               if (callback) callback(template);
           }
       });
    }

    Handlebars.registerHelper("inc", function(value, options)
    {
        return parseInt(value) + 1;
    });

    // The partial definition for displaying a form field
    var fieldPartialTest = "{{#this}}{{#if '@id'}}\
                                <button class='button remove-field-button' id='remove-field-{{parent.name}}{{inc @index}}'>-</button>\
                                <input data-range={{parent.range}} class='{{#ifCond parent.range 'resource'}}ldpLookup{{/ifCond}} {{#ifCond parent.hasLookup 'true'}}hasLookup{{/ifCond}}' id='{{parent.name}}{{inc @index}}' type='text' name='{{parent.name}}[]' value='{{'@id'}}' />\
                            {{/if}}{{/this}}";
    Handlebars.registerPartial("LDPFieldTest", fieldPartialTest);

    // The partial definition for displaying a form field handling array values, with possibility to add a field dynamically
    var fieldDisplayPartial = "{{#if name}}<label for='{{name}}'>{{label}}</label>\
                                    <button class='button add-field-button' id='add-field-{{name}}' onclick='return wpldp.addField(event);'>+</button>\
                                {{/if}}\
                                 <div id='field-{{name}}' data-range={{range}} {{#ifCond type 'resource'}}data-ldp-lookup='true'{{/ifCond}} {{#ifCond hasLookup 'true'}}data-has-lookup='true'{{/ifCond}}>\
                                   {{#if fields}}\
                                     {{#each fields }}\
                                       {{>LDPFieldTest this parent=../this}}\
                                     {{/each}}\
                                   {{else}} \
                                     {{log type}}\
                                     <input data-range={{range}} class='{{type}} {{#ifCond type 'resource'}}ldpLookup{{/ifCond}} {{#ifCond hasLookup 'true'}}hasLookup{{/ifCond}}' id='{{name}}' type='text' placeholder='{{title}}' name='{{name}}' />\
                                   {{/if}}\
                                 </div>";
    Handlebars.registerPartial("ArrayFieldDisplay", fieldDisplayPartial);

    this.addField = function addField( event ) {
       event.stopPropagation();
       event.preventDefault();

       var target_id = event.target.id.substring('add-'.length);

       var target_div = document.getElementById(target_id);
       var child_count = target_div.childElementCount + 1;
       var input = document.createElement('input');
       input.id = target_id.substring('field-'.length) + child_count;
       input.name = target_id.substring('field-'.length) + "[]";
       input.type = "text";
       if ( target_div.dataset && target_div.dataset.hasLookup == 'true' ) {
           input.className += ' hasLookup';
       } else if ( target_div.dataset && target_div.dataset.ldpLookup == 'true' ) {
           input.className += ' ldpLookup';
       }

       var remove_button = document.createElement('button');
       remove_button.id = "remove-field-" + target_id.substring('field-'.length) + child_count;
       remove_button.className = "button remove-field-button";
       remove_button.innerHTML = "-";

       target_div.appendChild(remove_button);
       target_div.appendChild(input);

       this.bindEvents();

       return false;
    }

    // The partial definition for displaying a form field
    var fieldPartial = "{{#ifCond multiple 'true'}}{{> ArrayFieldDisplay }}\
                        {{else}}\
                          {{#if name}}<label for='{{name}}'>{{label}}</label>{{/if}} \
                          {{#ifCond type 'textarea'}} \
                            {{#if name}}<textarea id='{{name}}' name='{{name}}' rows='10'>{{#if fieldValue}}{{fieldValue}}{{/if}}</textarea><br/>{{/if}}\
                          {{else}}\
                            {{#ifCond type 'checkbox'}} \
                              {{#if name}}<input type='checkbox' name='{{name}}' id='{{name}}'/>{{/if}}\
                            {{else}}\
                              {{#ifCond type 'select'}} \
                                {{#if name}}<select id='{{name}}' name='{{name}}'>{{/if}}\
                                  {{#each options}}{{> LDPOptions fieldValue='{{fieldValue}}' }}{{/each}} \
                              {{else}} \
                                {{#ifCond type 'date'}} \
                                  {{#if name}}<input id='{{name}}' type='date' placeholder='YYYY-MM-DD' name='{{name}}' value='{{fieldValue}}' />{{/if}}\
                                {{else}} \
                                  {{#ifCond type 'url'}} \
                                    {{#if name}}<input id='{{name}}' type='url' placeholder='http://www.example.com' name='{{name}}' value='{{fieldValue}}' />{{/if}}\
                                  {{else}} \
                                    {{#ifCond type 'email'}} \
                                      {{#if name}}<input id='{{name}}' type='email' placeholder='contact@example.com' name='{{name}}' value='{{fieldValue}}' />{{/if}}\
                                    {{else}} \
                                      {{#ifCond type 'resource'}} \
                                      <input id='{{name}}' type='url' placeholder='http://www.example.com/ldp/resource/my-resource/' name='{{name}}' value='{{fieldValue}}' />\
                                       {{else}} \
                                         {{#if name}}<input id='{{name}}' type='text' placeholder='{{title}}' name='{{name}}' value='{{fieldValue}}' />{{/if}}\
                                       {{/ifCond}}\
                                     {{/ifCond}}\
                                  {{/ifCond}}\
                                {{/ifCond}}\
                              {{/ifCond}}\
                            {{/ifCond}}\
                          {{/ifCond}}\
                         {{/ifCond}}";
    Handlebars.registerPartial("LDPField", fieldPartial);

    // The partial definition for displaying an option field inside a select
    var optionPartial = "{{#ifCond value fieldValue}} \
                          <option value='{{value}}' selected>{{name}}</option>\
                        {{else}}\
                          <option value='{{value}}'>{{name}}</option>\
                        {{/ifCond}}";
    Handlebars.registerPartial("LDPOptions", optionPartial);

    var formTemplate = Handlebars.compile(
        "<form data-container='{{container}}' onSubmit='return store.handleSubmit(event);'> \
            {{#each fields}}{{> LDPField }}{{/each}} \
            <input type='submit' value='Post' /> \
        </form>");

    this.registerPartialFromFile = function registerPartialFromFile( partialName, partialTemplatePath ) {
        jQuery.ajax({
            url: partialTemplatePath,
            success: function (data) {
                Handlebars.registerPartial(partialName, data);
            }
        });
    }

     Handlebars.registerHelper("ldpeach", function(array, tagName, options) {
         var id = "ldp-"+Math.round(new Date().getTime() + (Math.random() * 10000));
         var objects = Array.isArray(array) ? array : [array];
         objects.forEach(function(object) {
             this.store.get(object, this.store.context).then(function(object) {
                 jQuery('#'+id).append(options.fn(object));
             }.bind(this));
         }.bind(this));
         return '<'+ tagName +' id="'+id+'"></' + tagName + '>';
     }.bind(this));

     Handlebars.registerHelper('ldplist', function(obj) {
         return obj['ldp:contains'];
     });

     Handlebars.registerHelper('ifCond', function(value, tester, options) {
       if (value == tester) {
         return options.fn(this);
       } else {
         return options.inverse(this);
       }
     });

     Handlebars.registerHelper('if', function(conditional, options) {
      if(conditional) {
        return options.fn(this);
      }
     });

     Handlebars.registerHelper('form', function(context, options) {
         return formTemplate(this.store.models[context]);
     }.bind(this));
};
