jQuery(function ($) {

  // Initial States
  $(document).ready(function () {
    setup_widgets(function () {
      let connection_obj = window.dt_storage.previous_updated_connection_obj;
      if (connection_obj) {
        $('#m_main_col_available_connections_select').val(connection_obj['id']).trigger('change');
      }
    });
  });

  // Event Listeners
  $(document).on('click', '#m_main_col_available_connections_new', function () {
    handle_new_connection_request();
  });

  $(document).on('change', '#m_main_col_available_connections_select', function () {
    handle_load_connection_request();
  });

  $(document).on('click', '#m_main_col_delete_but', function () {
    handle_delete_connection_request();
  });

  $(document).on('change', '#m_main_col_connection_manage_type', function () {
    const connection_type = $('#m_main_col_connection_manage_type').val();
    const connection_obj = fetch_connection_obj($('#m_main_col_available_connections_select').val());
    handle_connection_type_select( connection_type, connection_obj );
  });

  $(document).on('click', '.connection_type_show_hide_but', function (e) {
    const show_hide_but = $(e.target);
    const input = $( '#' + $(show_hide_but).data('input') );

    const show = $(input).attr('type') === 'password';
    $(input).attr('type', show ? 'text' : 'password');
    $(show_hide_but).text( show ? 'Hide' : 'Show' );
  });

  $(document).on('click', '#connection_type_s3_test_but', function () {
    handle_connection_test_for_s3();
  });

  $(document).on('click', '.m-connection-update-but', function () {
    handle_update_request();
  });

  // Event Listeners - Helper Functions
  function setup_widgets(callback) {
    refresh_section_available_connections( window.dt_storage.connection_objs );

    callback();
  }

  function handle_new_connection_request() {
    reset_sections();
  }

  function handle_load_connection_request() {
    let connection_obj = fetch_connection_obj($('#m_main_col_available_connections_select').val());
    if (connection_obj) {

      reset_section(true, $('#m_main_col_connection_manage'), function () {
        reset_section_connection_manage(connection_obj['id'], connection_obj['enabled'], connection_obj['name'], connection_obj['type']);

        // Display the corresponding selected type settings and populate fields.
        handle_connection_type_select( connection_obj['type'], connection_obj );
      });

      $('#m_main_col_delete_but').fadeIn('fast');
    }
  }

  function fetch_connection_obj(id) {
    let connection_obj = null;

    $.each(window.dt_storage.connection_objs, function (idx, obj) {
      if (String(idx).trim() === String(id).trim()) {
        connection_obj = obj;
      }
    });

    return connection_obj;
  }

  function handle_delete_connection_request() {
    let id = $('#m_main_col_connection_manage_id').val();
    let name = $('#m_main_col_connection_manage_name').val();

    if (id && confirm(`Are you sure you wish to delete ${name}?`)) {
      $('#m_main_col_delete_form_connection_obj_id').val(id);
      $('#m_main_col_delete_form').submit();
    }
  }

  function reset_sections() {
    reset_section_available_connections();

    reset_section(true, $('#m_main_col_connection_manage'), function () {
      let timestamp = new Date().getTime();
      reset_section_connection_manage(timestamp, true, '', '');
    });

    reset_section(false, $('#m_main_col_connection_type_details'), function () {});

    $('#m_main_col_delete_but').fadeOut('fast');
  }

  function reset_section(display, section, reset_element_func) {
    section.fadeOut('fast', function () {

      // Reset elements
      reset_element_func();

      // If flagged to do so, display sub-section
      if (display) {
        section.fadeIn('fast');
      }
    });
  }

  function reset_section_available_connections() {
    $('#m_main_col_available_connections_select').val('');
  }

  function refresh_section_available_connections(connections = {}) {
    let connection_objs_select = $('#m_main_col_available_connections_select');
    $(connection_objs_select).empty();
    $(connection_objs_select).append($('<option/>').prop('disabled', true).prop('selected', true).val('').text('-- select available connection --'));

    $.each(connections, function (id, connection) {
      $(connection_objs_select).append($('<option/>').val(window.dt_admin_shared.escape(id)).text(window.dt_admin_shared.escape(connection.name)));
    });

    reset_section_available_connections();
  }

  function reset_section_connection_manage(id, enabled, name, type) {
    $('#m_main_col_connection_manage_id').val(id);
    $('#m_main_col_connection_manage_enabled').prop('checked', enabled);
    $('#m_main_col_connection_manage_name').val(name);
    $('#m_main_col_connection_manage_type').val(type);
  }

  function handle_connection_type_select( selected_type, connection_obj = null ) {
    const is_multisite = window.dt_storage.is_multisite === '1';
    const connection_types = window.dt_storage.connection_types;
    const connection_type_details = $('#m_main_col_connection_type_details');
    const connection_type_details_content = $('#m_main_col_connection_type_details_content');

    // Placeholder for populating type settings.
    let connection_type_refresh = function() {};

    // Refresh connection type details section accordingly by selected type api.
    $(connection_type_details).fadeOut('fast', function () {
      if ( connection_types[selected_type] && connection_types[selected_type]['enabled'] === true ) {
        const connection_type = connection_types[selected_type];

        // Display widgets accordingly based on type's api.
        let html = null;
        switch ( connection_type['api'] ) {
          case 's3': {
            html = generate_type_details_html_for_s3();

            // If valid obj supplied, specify refresh function logic.
            if (connection_obj && connection_obj[selected_type]) {
              connection_type_refresh = function() {
                const type = connection_obj[selected_type];
                const secret_access_key = $('#connection_type_s3_secret_access_key');

                $('#connection_type_s3_access_key').val( type['access_key'] );
                $(secret_access_key).val( type['secret_access_key'] );
                $('#connection_type_s3_region').val( type['region'] );
                $('#connection_type_s3_bucket').val( type['bucket'] );
                $('#connection_type_s3_endpoint').val( type['endpoint'] );

                // Handle any/all multisite specific tasks.
                if ( is_multisite ) {
                  if ( connection_obj?.source === 'multisite' ) {
                    $(secret_access_key).parent().parent().remove();
                  }
                }
              };
            }

            break;
          }
        }

        // Display any generated html.
        if ( html ) {
          $(connection_type_details_content).html( html );
          $(connection_type_details).fadeIn('fast', connection_type_refresh);
        }
      }
    });
  }

  function generate_type_details_html_for_s3() {
    return `
    <table class="widefat striped">
        <tr>
            <td style="vertical-align: middle;">Access Key</td>
            <td>
                <input style="min-width: 80%;" type="password" id="connection_type_s3_access_key" value=""/>
                <span style="float:right;">
                    <button class="button connection_type_show_hide_but" data-input="connection_type_s3_access_key">Show</button>
                </span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: middle;">Secret Access Key</td>
            <td>
                <input style="min-width: 80%;" type="password" id="connection_type_s3_secret_access_key" value=""/>
                <span style="float:right;">
                    <button class="button connection_type_show_hide_but" data-input="connection_type_s3_secret_access_key">Show</button>
                </span>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: middle;">Region</td>
            <td>
                <input style="min-width: 100%;" type="text" id="connection_type_s3_region" value=""/>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: middle;">Endpoint</td>
            <td>
                <input style="min-width: 100%;" type="text" id="connection_type_s3_endpoint" value=""/>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: middle;">Bucket</td>
            <td>
                <input style="min-width: 100%;" type="text" id="connection_type_s3_bucket" value=""/>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: middle;" colspan="2">
                <button id="connection_type_s3_test_but" class="button" style="min-width: 100%;"><span id="connection_type_s3_test_but_content" style="margin-bottom: 2px; margin-top: 2px; font-size: 17px;">Test Connection</span></button>
            </td>
        </tr>
    </table>
    `;
  }

  function validate_url( url ) {
    try {

      // Convert to url object.
      const url_obj = new URL(url);

      // Ensure correct protocols have been specified.
      return ( ( url_obj.protocol === 'http:' ) || ( url_obj.protocol === 'https:' ) ) ?  url : ( 'https://' + url );

    } catch (err) {
      return 'https://' + url;
    }
  }

  function validate_connection_details( payload, callback ) {
    $.ajax({
      url: window.dt_storage.dt_endpoint_validate_connection,
      method: 'POST',
      data: payload,
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", window.dt_admin_scripts.nonce);
      },
      success: function (data) {
        callback( data );
      },
      error: function (data) {
        callback( data );
      }
    });
  }

  function handle_connection_test_for_s3() {

    // Trigger spinner!
    const test_but_content = $('#connection_type_s3_test_but_content');
    $(test_but_content).text('').addClass('loading-spinner active');

    try {

      // Fetch the specified credentials.
      const id = $('#m_main_col_connection_manage_id').val();
      const access_key = $('#connection_type_s3_access_key').val();
      const secret_access_key = $('#connection_type_s3_secret_access_key').val();
      const region = $('#connection_type_s3_region').val();
      const bucket = $('#connection_type_s3_bucket').val();
      const endpoint = validate_url( $('#connection_type_s3_endpoint').val() );

      // Request backend connection validation test.
      validate_connection_details({
          'connection_id': id,
          'connection_type_api': 's3',
          's3': {
            'access_key': access_key,
            'secret_access_key': secret_access_key,
            'region': region,
            'bucket': bucket,
            'endpoint': endpoint
          }
        },
        function (response) {
          $(test_but_content).removeClass('loading-spinner active').text( ( response?.valid ? 'Connection Successful!' : 'Connection Failed!' ) );
        }
      );

    } catch ( error ) {
      console.log( error );
      $(test_but_content).removeClass('loading-spinner active').text('Connection Failed!');
    }
  }

  function handle_update_request() {
    let connection_obj = {};

    // Fetch default settings.....
    const connection_types = window.dt_storage.connection_types;

    const id = $('#m_main_col_connection_manage_id').val();
    const enabled = $('#m_main_col_connection_manage_enabled').prop('checked');
    const name = $('#m_main_col_connection_manage_name').val();
    const type = $('#m_main_col_connection_manage_type').val();

    // Validate required default settings.
    if (!id) {
      alert('Unable to detect connection id. Please refresh page to fix!');
    } else if (!name) {
      alert('Please ensure a valid connection name has been specified.');
    } else if (!type) {
      alert('Please ensure a valid connection type has been selected.');
    } else {

      // Package validated default settings.
      connection_obj['id'] = id;
      connection_obj['enabled'] = enabled;
      connection_obj['name'] = name;
      connection_obj['type'] = type;

      // Fetch selected connection type settings.
      if (connection_types[type] && connection_types[type]['enabled'] === true) {
        const connection_type = connection_types[type];
        switch ( connection_type['api'] ) {
          case 's3': {
            connection_obj[type] = package_updates_for_s3();
            break;
          }
        }
      }

      // Submit packaged updates for backend saving.
      console.log(connection_obj);
      $('#m_main_col_update_form_connection_obj').val(JSON.stringify(connection_obj));
      $('#m_main_col_update_form').submit();
    }
  }

  function package_updates_for_s3() {
    return {
      'access_key': $('#connection_type_s3_access_key').val(),
      'secret_access_key': $('#connection_type_s3_secret_access_key').val(),
      'region': $('#connection_type_s3_region').val(),
      'bucket': $('#connection_type_s3_bucket').val(),
      'endpoint': $('#connection_type_s3_endpoint').val()
    }
  }

});
