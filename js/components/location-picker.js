/**
 * ShipperShop Component — Location Picker
 * Province → District → Ward cascade using provinces.open-api.vn
 */
window.SS = window.SS || {};

SS.LocationPicker = {
  _provEl: null,
  _distEl: null,
  _wardEl: null,
  _provinces: [],

  init: function(provId, distId, wardId) {
    SS.LocationPicker._provEl = document.getElementById(provId);
    SS.LocationPicker._distEl = document.getElementById(distId);
    SS.LocationPicker._wardEl = document.getElementById(wardId);

    if (!SS.LocationPicker._provEl) return;

    SS.LocationPicker._provEl.addEventListener('change', function() {
      SS.LocationPicker._onProvChange(this.value);
    });
    if (SS.LocationPicker._distEl) {
      SS.LocationPicker._distEl.addEventListener('change', function() {
        SS.LocationPicker._onDistChange(this.value);
      });
    }

    SS.LocationPicker.loadProvinces();
  },

  loadProvinces: function() {
    var el = SS.LocationPicker._provEl;
    if (!el) return;
    el.innerHTML = '<option value="">Tỉnh/Thành phố</option>';

    fetch('https://provinces.open-api.vn/api/?depth=1')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        SS.LocationPicker._provinces = data;
        for (var i = 0; i < data.length; i++) {
          var opt = document.createElement('option');
          opt.value = data[i].code;
          opt.textContent = data[i].name;
          el.appendChild(opt);
        }
      })
      .catch(function() {});
  },

  _onProvChange: function(code) {
    var distEl = SS.LocationPicker._distEl;
    var wardEl = SS.LocationPicker._wardEl;
    if (distEl) distEl.innerHTML = '<option value="">Quận/Huyện</option>';
    if (wardEl) wardEl.innerHTML = '<option value="">Xã/Phường</option>';
    if (!code) return;

    fetch('https://provinces.open-api.vn/api/p/' + code + '?depth=2')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var districts = data.districts || [];
        for (var i = 0; i < districts.length; i++) {
          var opt = document.createElement('option');
          opt.value = districts[i].code;
          opt.textContent = districts[i].name;
          if (distEl) distEl.appendChild(opt);
        }
      })
      .catch(function() {});
  },

  _onDistChange: function(code) {
    var wardEl = SS.LocationPicker._wardEl;
    if (wardEl) wardEl.innerHTML = '<option value="">Xã/Phường</option>';
    if (!code) return;

    fetch('https://provinces.open-api.vn/api/d/' + code + '?depth=2')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var wards = data.wards || [];
        for (var i = 0; i < wards.length; i++) {
          var opt = document.createElement('option');
          opt.value = wards[i].code;
          opt.textContent = wards[i].name;
          wardEl.appendChild(opt);
        }
      })
      .catch(function() {});
  },

  getSelected: function() {
    var provEl = SS.LocationPicker._provEl;
    var distEl = SS.LocationPicker._distEl;
    var wardEl = SS.LocationPicker._wardEl;
    return {
      province: provEl ? provEl.options[provEl.selectedIndex].text : '',
      district: distEl ? distEl.options[distEl.selectedIndex].text : '',
      ward: wardEl ? wardEl.options[wardEl.selectedIndex].text : '',
      province_code: provEl ? provEl.value : '',
      district_code: distEl ? distEl.value : '',
      ward_code: wardEl ? wardEl.value : ''
    };
  },

  clear: function() {
    if (SS.LocationPicker._provEl) SS.LocationPicker._provEl.value = '';
    if (SS.LocationPicker._distEl) SS.LocationPicker._distEl.innerHTML = '<option value="">Quận/Huyện</option>';
    if (SS.LocationPicker._wardEl) SS.LocationPicker._wardEl.innerHTML = '<option value="">Xã/Phường</option>';
  }
};
