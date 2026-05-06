jQuery(document).ready(function () {
  _registerElControl();
});

function _registerElControl() {
  const mySelect2ItemView = elementor.modules.controls.BaseData.extend({
    onReady: function () {
      _handleRegisterSelect2(this);
    },

    saveValue: function () {
      const data = this.ui.select.select2("val");
      this.setValue(data);
    },

    onBeforeDestroy: function () {
      this.saveValue();
      this.ui.select.select2("destroy");
      this.ui.select.off("select2:select");
    },
  });

  elementor.addControlView("wil_select2_ajax", mySelect2ItemView);
}

async function _handleRegisterSelect2(self) {
  let selectEl = self.ui.select;

  // === Default Settings === //
  const restApi = self.model.get("endpoint");
  const apiArgs = self.model.get("api_args");
  const multiple = self.model.get("multiple");
  const placeholder = self.model.get("placeholder");
  const minimumInputLength = self.model.get("minimumInputLength");

  //===
  const defaultIds = selectEl.attr("data-values")
    ? selectEl.attr("data-values").split(",")
    : [];
  // ===

  const data = await _getAjaxFromDefaultIds(defaultIds, restApi);
  console.log(typeof data);
  // ===
  const initData = defaultIds.map((id) => {
    const oItem = data.filter((i) => String(i.id) === id)[0] || {};
    const val = _getOptionText(oItem);
    return { id: oItem.id || id, text: val, selected: true };
  });

  // ===
  selectEl.select2({
    data: initData,
    ajax: {
      url: restApi,
      dataType: "json",
      delay: 250,
      complete: function (xhr) {
        totalPages = xhr.getResponseHeader("X-WP-TotalPages");
      },
      data: function (params) {
        return {
          per_page: 20,
          ...apiArgs,
          page: params.page,
          search: params.term,
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;

        return {
          results: data.map((i) => {
            const text = _getOptionText(i);
            return { id: i.id, text };
          }),
          pagination: { more: params.page < totalPages },
        };
      },
      cache: false,
    },
    templateResult: _formatItem,
    templateSelection: _formatItemSelection,
    placeholder,
    minimumInputLength,
    multiple,
    allowClear: true,
  });
}

function _formatItem(item) {
  if (item.loading) {
    return item.text;
  }
  return item.text || item.id;
}

function _formatItemSelection(item) {
  return item.text || item.id;
}

async function _getAjaxFromDefaultIds(defaultIds, restApi) {
  if (!Array.isArray(defaultIds) || !defaultIds.length) {
    return [];
  }
  try {
    const result = await jQuery.get(restApi, { include: defaultIds });
    return result;
  } catch (error) {
    console.error(error);
    return [];
  }
}

function _getOptionText(oItem = {}) {
  return oItem.name
    ? oItem.name
    : oItem.title
    ? oItem.title.rendered
    : oItem.id;
}
