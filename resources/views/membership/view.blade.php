@extends('layouts/application')
@section('head')
<style>
  .membership-toast-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 1080;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }

  .membership-toast {
    min-width: 260px;
    max-width: 360px;
    padding: 12px 16px;
    border-radius: 8px;
    color: #fff;
    background: #1f7a45;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    pointer-events: auto;
  }

  .membership-toast.show {
    opacity: 1;
    transform: translateY(0);
  }

  .membership-toast__title {
    display: block;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .membership-toast__message {
    display: block;
    line-height: 1.4;
  }

  .membership-status-badge {
    display: inline-block;
    min-width: 84px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    text-align: center;
  }

  .membership-status-badge.active {
    background: rgba(31, 122, 69, 0.15);
    color: #1f7a45;
  }

  .membership-status-badge.expired {
    background: rgba(198, 60, 45, 0.14);
    color: #c63c2d;
  }

  .membership-discount-display {
    font-weight: 700;
    color: #275d96;
  }
</style>
@endsection
@section('content')
<div class="container">
  <div class="membership-toast-container" id="membershipToastContainer" aria-live="polite" aria-atomic="true"></div>
  <div class="row" style="margin-top:100px;">
    <table id="MembershipTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th class="hidden-xs">{{ __('messages.sale_table_number') }}</th>
          <th>{{ __('messages.input_date') }}</th>
          <th>{{ __('messages.phone') }}</th>
          <th>{{ __('messages.name') }}</th>
          <th>{{ __('messages.membership_rank') }}</th>
          <th>{{ __('messages.membership_discount') }}</th>
          <th>{{ __('messages.membership_years') }}</th>
          <th>{{ __('messages.expire_date') }}</th>
          <th>{{ __('messages.status') }}</th>
          <th>{{ __('messages.action') }}</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
  <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#AddMembership">{{ __('messages.add_membership') }}</button>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    const csrfToken = @json(csrf_token());
    const canDelete = @json(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin());
    const membershipToastTitle = @json(__('messages.success'));
    const membershipCreatedMessage = @json(__('messages.membership_created'));
    const membershipUpdatedMessage = @json(__('messages.membership_updated'));
    const membershipDeletedMessage = @json(__('messages.membership_deleted'));
    const activeLabel = @json(__('messages.active'));
    const expiredLabel = @json(__('messages.expired'));
    const defaultRank = 'VIP';
    const today = new Date();

    function getMembershipTableLanguage() {
      const currentLang = $('html').attr('lang') || 'en';

      if (currentLang !== 'kh') {
        return {};
      }

      return {
        processing: "ដំណើរការ...",
        search: "ស្វែងរក:",
        lengthMenu: "បង្ហាញ _MENU_ ធាតុ",
        info: "បង្ហាញ _START_ ដល់ _END_ នៃ _TOTAL_ ធាតុ",
        infoEmpty: "បង្ហាញ 0 ដល់ 0 នៃ 0 ធាតុ",
        infoFiltered: "(បានចម្រាញ់ចេញពី _MAX_ ធាតុសរុប)",
        loadingRecords: "កំពុងផ្ទុកទិន្នន័យ...",
        zeroRecords: "មិនមានទិន្នន័យត្រូវបង្ហាញ",
        emptyTable: "មិនមានទិន្នន័យក្នុងតារាង",
        paginate: {
          first: "ទំព័រដំបូង",
          previous: "មុន",
          next: "បន្ទាប់",
          last: "ទំព័រចុងក្រោយ"
        }
      };
    }

    function showMembershipToast(message) {
      const container = document.getElementById('membershipToastContainer');

      if (!container) {
        return;
      }

      const toast = document.createElement('div');
      toast.className = 'membership-toast';
      toast.innerHTML = `
        <span class="membership-toast__title">${membershipToastTitle}</span>
        <span class="membership-toast__message">${message}</span>
      `;

      container.appendChild(toast);

      window.requestAnimationFrame(function() {
        toast.classList.add('show');
      });

      window.setTimeout(function() {
        toast.classList.remove('show');
        window.setTimeout(function() {
          toast.remove();
        }, 200);
      }, 3000);
    }

    function renderActionButtons() {
      let buttons = '';

      if (canDelete) {
        buttons += '<div class="btn-group"><a href="#" class="btn btn-default delete-membership"><i class="fa fa-times"></i></a></div>';
      }

      buttons += '<div class="btn-group"><a href="#" class="btn btn-default edit-membership"><i class="fa fa-pencil-square-o"></i></a></div>';
      return buttons;
    }

    function discountForRank(rank) {
      return rank === 'DIAMOND' ? 6 : 5;
    }

    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function addYears(baseDate, years) {
      const date = new Date(baseDate.getTime());
      date.setFullYear(date.getFullYear() + years);
      return date;
    }

    function setDefaultAddForm() {
      $('#membershipAddForm')[0].reset();
      $('#membership-rank').val(defaultRank);
      $('#membership-discount').val(`${discountForRank(defaultRank)}%`);
      $('#membership-years').val(1);
      $('#membership-expired-at').val(formatDate(addYears(today, 1)));
    }

    function updateDiscountPreview($rankField, $discountField) {
      const rank = $rankField.val() || defaultRank;
      $discountField.val(`${discountForRank(rank)}%`);
    }

    function updateExpirePreview($yearsField, $expireField) {
      const years = Math.max(parseInt($yearsField.val(), 10) || 1, 1);
      $yearsField.val(years);
      $expireField.val(formatDate(addYears(today, years)));
    }

    const membershipTable = $('#MembershipTable').DataTable({
      processing: true,
      serverSide: true,
      searchDelay: 350,
      ajax: {
        url: '{{ route("membership.data") }}',
        type: 'GET'
      },
      order: [[1, 'desc']],
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      language: getMembershipTableLanguage(),
      columns: [
        { data: 'row_number', searchable: false, orderable: false, className: 'hidden-xs' },
        { data: 'updated_at_display', name: 'updated_at' },
        { data: 'phone', name: 'phone' },
        { data: 'name', name: 'name' },
        { data: 'rank', name: 'rank' },
        { data: 'discount_display', name: 'discount_percent', className: 'membership-discount-display' },
        { data: 'membership_years_display', name: 'membership_years' },
        { data: 'expired_at_display', name: 'expired_at' },
        {
          data: 'status_label',
          name: 'expired_at',
          render: function(data, type, row) {
            if (type !== 'display') {
              return row.status_key;
            }

            return `<span class="membership-status-badge ${row.status_key}">${data}</span>`;
          }
        },
        {
          data: null,
          searchable: false,
          orderable: false,
          render: function() {
            return renderActionButtons();
          }
        }
      ]
    });

    setDefaultAddForm();
    $('.membership-datepicker').datepicker({
      format: 'yyyy-mm-dd',
      autoclose: true,
      todayHighlight: true
    });

    $('#AddMembership').on('show.bs.modal', function() {
      setDefaultAddForm();
    });

    $('#membership-rank').on('change', function() {
      updateDiscountPreview($(this), $('#membership-discount'));
    });

    $('#membership-years').on('input', function() {
      updateExpirePreview($(this), $('#membership-expired-at'));
    });

    $('#membership-edit-rank').on('change', function() {
      updateDiscountPreview($(this), $('#membership-edit-discount'));
    });

    $('#membership-edit-years').on('input', function() {
      updateExpirePreview($(this), $('#membership-edit-expired-at'));
    });

    $('#membershipAddForm').on('submit', function(e) {
      e.preventDefault();

      $.ajax({
        url: '/membership/add',
        type: 'POST',
        data: {
          _token: csrfToken,
          phone: $('#membership-phone').val(),
          name: $('#membership-name').val(),
          rank: $('#membership-rank').val(),
          membership_years: $('#membership-years').val(),
          expired_at: $('#membership-expired-at').val()
        },
        success: function(response) {
          if (response.code !== 200) {
            return;
          }

          $('#AddMembership').modal('hide');
          membershipTable.ajax.reload(null, false);
          showMembershipToast(response.message || membershipCreatedMessage);
        },
        error: function(xhr) {
          const errors = xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : '{{ __('messages.membership_error') }}';
          swal({ title: '{{ __('messages.error') }}', text: errors, type: 'error' });
        }
      });
    });

    $('#MembershipTable').on('click', '.edit-membership', function(e) {
      e.preventDefault();
      const row = membershipTable.row($(this).closest('tr')).data();

      if (!row) {
        return;
      }

      $('#membership-edit-id').val(row.id);
      $('#membership-edit-phone').val(row.phone);
      $('#membership-edit-name').val(row.name);
      $('#membership-edit-rank').val(row.rank);
      $('#membership-edit-discount').val(`${row.discount_percent}%`);
      $('#membership-edit-years').val(row.membership_years);
      $('#membership-edit-expired-at').val(row.expired_at);
      $('#EditMembership').modal('show');
    });

    $('#membershipEditForm').on('submit', function(e) {
      e.preventDefault();

      $.ajax({
        url: '/membership/update',
        type: 'POST',
        data: {
          _token: csrfToken,
          id: $('#membership-edit-id').val(),
          phone: $('#membership-edit-phone').val(),
          name: $('#membership-edit-name').val(),
          rank: $('#membership-edit-rank').val(),
          membership_years: $('#membership-edit-years').val(),
          expired_at: $('#membership-edit-expired-at').val()
        },
        success: function(response) {
          if (response.code !== 200) {
            return;
          }

          $('#EditMembership').modal('hide');
          membershipTable.ajax.reload(null, false);
          showMembershipToast(response.message || membershipUpdatedMessage);
        },
        error: function(xhr) {
          const errors = xhr.responseJSON && xhr.responseJSON.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : '{{ __('messages.membership_error') }}';
          swal({ title: '{{ __('messages.error') }}', text: errors, type: 'error' });
        }
      });
    });

    $('#MembershipTable').on('click', '.delete-membership', function(e) {
      e.preventDefault();
      const row = membershipTable.row($(this).closest('tr')).data();

      if (!row) {
        return;
      }

      swal({
        title: '{{ __('messages.are_you_sure') }}',
        text: '{{ __('messages.delete_confirm') }}',
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DD6B55',
        confirmButtonText: '{{ __('messages.yes') }}',
        closeOnConfirm: true
      }, function() {
        $.ajax({
          url: '/membership/delete',
          type: 'GET',
          data: { id: row.id },
          success: function(response) {
            if (response.code !== 200) {
              return;
            }

            membershipTable.ajax.reload(null, false);
            showMembershipToast(response.message || membershipDeletedMessage);
          },
          error: function() {
            swal({ title: '{{ __('messages.error') }}', text: '{{ __('messages.membership_error') }}', type: 'error' });
          }
        });
      });
    });
  });
</script>

<div class="modal fade" id="AddMembership" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="membershipAddForm">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">{{ __('messages.add_membership') }}</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="membership-phone">{{ __('messages.phone') }}</label>
            <input type="text" id="membership-phone" class="form-control" maxlength="50" required>
          </div>
          <div class="form-group">
            <label for="membership-name">{{ __('messages.name') }}</label>
            <input type="text" id="membership-name" class="form-control" maxlength="255" required>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-rank">{{ __('messages.membership_rank') }}</label>
                <select id="membership-rank" class="form-control">
                  <option value="VIP">VIP</option>
                  <option value="DIAMOND">DIAMOND</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-discount">{{ __('messages.membership_discount') }}</label>
                <input type="text" id="membership-discount" class="form-control" readonly>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-years">{{ __('messages.membership_years') }}</label>
                <input type="number" id="membership-years" class="form-control" min="1" max="20" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-expired-at">{{ __('messages.expire_date') }}</label>
                <input type="text" id="membership-expired-at" class="form-control membership-datepicker" placeholder="YYYY-MM-DD" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
          <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="EditMembership" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="membershipEditForm">
        <input type="hidden" id="membership-edit-id">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">{{ __('messages.edit_membership') }}</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="membership-edit-phone">{{ __('messages.phone') }}</label>
            <input type="text" id="membership-edit-phone" class="form-control" maxlength="50" required>
          </div>
          <div class="form-group">
            <label for="membership-edit-name">{{ __('messages.name') }}</label>
            <input type="text" id="membership-edit-name" class="form-control" maxlength="255" required>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-edit-rank">{{ __('messages.membership_rank') }}</label>
                <select id="membership-edit-rank" class="form-control">
                  <option value="VIP">VIP</option>
                  <option value="DIAMOND">DIAMOND</option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-edit-discount">{{ __('messages.membership_discount') }}</label>
                <input type="text" id="membership-edit-discount" class="form-control" readonly>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-edit-years">{{ __('messages.membership_years') }}</label>
                <input type="number" id="membership-edit-years" class="form-control" min="1" max="20" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label for="membership-edit-expired-at">{{ __('messages.expire_date') }}</label>
                <input type="text" id="membership-edit-expired-at" class="form-control membership-datepicker" placeholder="YYYY-MM-DD" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
          <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
