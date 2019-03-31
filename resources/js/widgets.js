//see https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding#The_.22Unicode_Problem.22
function b64EncodeUnicode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function (match, p1) {
        return String.fromCharCode('0x' + p1);
    }));
}

//TODO jquery ui seems obsolete, use something else?

$.widget("custom.gov", {
    options: {
        //initial
        fastUrl: null,
        slowUrl: null,
        govResetHtml: "",
        //initial
        fastAjax: true,
        canEdit: false,
        name: "",
        placeId: "",
        julianDay: 0,
        julianDayText: "",
        type: "MAIN",
        i18n: null, //'static' texts
        locale: null,
        compactDisplay: false,
        allowSettlements: false,

        //initial + internal
        id: null,
        version: null,

        //internal
        nextId: null,
        invalidId: null,
        resetTriggered: false,
        reloadTriggered: false,
        namesAndTypes: [],

        //helper
        safeClassName: null
    },

    _create: function () {
        //type no longer relevant here!
        //this.options.safeClassName = "govWidgetClass-" + b64EncodeUnicode(this.options.placeId + this.options.type).replace(/=/g, "");
        this.options.safeClassName = "govWidgetClass-" + b64EncodeUnicode(this.options.placeId).replace(/=/g, "");
        this.element.addClass(this.options.safeClassName);

        //main text
        this.element.append('<span class="govText"/>');

        //set/reset form
        var form = $(
                '<form action="/">' +
                '	<input type="text" name="id" value="">' +
                '	<input type="submit" value="">' +
                '</form>');
        this.element.append(form.hide());

        //reset button (do not add inner html now)
        var reset = $(
                '<span class="govReset" title="Reset"><span class="govResetIcon"/><span class="govResetHtml"/></span>');
        this.element.append(reset.hide());

        //sub text
        this.element.append('<span class="govText2"/>');

        var widget = this;
        this._on(form, {
            "submit": function (event) {
                event.preventDefault();
                var id = form.find("input[name=\'id\']").val();
                var version = new Date().getTime();
                var offset = "placeId=" + this.options.placeId + "&type=" + this.options.type + "&id=" + id + "&version=" + version + "&julianDay=" + this.options.julianDay + "&locale=" + this.options.locale + "&allowSettlements=" + this.options.allowSettlements;
                var fast = this.options.fastAjax;
                var onSuccess = function (content) {
                    var json = JSON.parse(content);
                    if (!json.type || !json.label) {
                        widget._setOptions({"invalidId": id});
                        return;
                    }

                    //trigger change for all widgets with this name!
                    $("." + widget.options.safeClassName).each(function () {
                        $(this).gov("instance")._setOptions({
                            "fastAjax": fast,
                            "id": id,
                            "version": version,
                            "nextId": json.nextId,
                            "invalidId": null,
                            "resetTriggered": false,
                            "reloadTriggered": true,
                            "namesAndTypes": [{name: json.label, type: json.type, id: id}]
                        });
                    });
                };
                var ajaxGet = fast ? $.get(this.options.fastUrl + offset) : $.get(this.options.slowUrl + offset);
                if (fast) {
                    ajaxGet.fail(function () {
                        //switch to slow requests
                        fast = false;
                        var ajaxGet2 = $.get(this.options.slowUrl + offset + "&switchToSlowAjax=true");
                        ajaxGet2.done(onSuccess);
                    });
                }
                ajaxGet.done(onSuccess);
            }
        });

        this._on(reset, {
            "click": function (event) {
                widget._setOptions({"resetTriggered": true});
            }
        });

        //do not refresh now: too expensive!				
        //this._refresh();
    },

    _setOptions: function () {
        this._superApply(arguments);
        this._refresh();
    },

    _refresh: function () {
        this.element.find(".govReset").hide();

        var span = '<a href="http://gov.genealogy.net/" target="_blank">GOV</a> (';
        span += this.options.julianDayText;
        span += '): ';

        //form
        var form = this.element.find("form");
        if ((this.options.id == null) || this.options.invalidId || this.options.resetTriggered) {

            var command = this.options.i18n.setCommand;

            if (this.options.invalidId) {
                if (this.options.idViaGedcom) {
                    span += this.options.i18n.invalidIdViaGedcomText;
                } else {
                    span += this.options.i18n.invalidIdText;
                }

            } else if (this.options.resetTriggered) {
                if (this.options.idViaGedcom) {
                    span += this.options.i18n.reloadText;
                } else {
                    span += this.options.i18n.resetText;
                }
                if (this.options.idViaGedcom) {
                    command = this.options.i18n.reloadCommand;
                } else {
                    command = this.options.i18n.resetCommand;
                }
            } else {
                span += this.options.i18n.setText;
            }

            this.element.find(".govText").html(span);

            if (this.options.invalidId && this.options.idViaGedcom) {
                //no form
                form.hide();

                return;
            }

            if (this.options.canEdit) {
                if (this.options.invalidId) {
                    form.find("input[name='id']").val("");
                } else if (this.options.id) {
                    form.find("input[name='id']").val(this.options.id);
                }

                if (this.options.idViaGedcom) {
                    form.find("input[name='id']").prop("readonly", true);
                } else if (this.options.id) {
                    form.find("input[name='id']").prop("readonly", false);
                }

                form.find("input[type='submit']").val(command);
                form.show();
            }

            this.element.find(".govText2").empty();
            return;
        }

        //no form
        form.hide();

        //text
        var span2 = '';
        for (var i = 0; i < this.options.namesAndTypes.length; i++) {
            span += '<a href="http://gov.genealogy.net/item/show/' + this.options.namesAndTypes[i].id + '" target="_blank" title="' + this.options.namesAndTypes[i].type + ' ' + this.options.namesAndTypes[i].name + '">';
            if (!this.options.compactDisplay) {
                span2 += this.options.namesAndTypes[i].type;
                if (i + 1 < this.options.namesAndTypes.length) {
                    span2 += ', ';
                }
            }
            span += this.options.namesAndTypes[i].name;
            span += '</a>';
            if (i + 1 < this.options.namesAndTypes.length) {
                span += ', ';
            }
        }
        if (span2) {
            span2 = '</span><br/><span>(' + this.options.i18n.adminLevels + ': ' + span2 + ')';
        }

        var expandId = this.options.nextId;
        if (!expandId) {
            if (this.options.namesAndTypes.length == 0) {
                //initial expand		
                expandId = this.options.id;
            } else {
                //we're done!
                this.element.find(".govText").html(span);
                this.element.find(".govText2").html(span2);

                if (this.options.canEdit) {
                    this.element.find(".govReset").attr("title", this.options.i18n.resetButton);
                    this.element.find(".govReset").show();                    
                                        
                    if (!this.options.reloadTriggered) {
                      this.element.find(".govResetHtml").show();
                      this.element.find(".govResetHtml").html(this.options.govResetHtml);
                    } else {
                      //cleaned up now - html not required to show anymore. (assuming html is only for this special use case)
                      this.element.find(".govResetHtml").hide();
                    }                    
                }
                return;
            }
        }

        //TODO spinner doesn't show up reliably in short hierarchies
        span += '<span class="govSpinner"/>';
        this.element.find(".govText").html(span);
        this.element.find(".govText2").html(span2);

        var widget = this;

        var offset = "id=" + expandId + "&version=" + this.options.version + "&julianDay=" + this.options.julianDay + "&locale=" + this.options.locale + "&allowSettlements=" + this.options.allowSettlements;
        var fast = this.options.fastAjax;
        var onSuccess = function (content) {
            var json = JSON.parse(content);
            if (!json.type || !json.label) {
                if (widget.options.idViaGedcom) {
                    widget._setOptions({"invalidId": widget.options.id});
                    return;
                }
                window.alert("Internal error!");
                return;
            }

            var nameAndType = {name: json.label, type: json.type, id: expandId};
            //this leads to data being shared incorrectly between widget instances, not really sure why 
            //widget.options.namesAndTypes.push(nameAndType);

            var namesAndTypes = widget.options.namesAndTypes.slice(0);
            namesAndTypes.push(nameAndType);

            widget._setOptions({"fastAjax": fast, "nextId": json.nextId, "namesAndTypes": namesAndTypes});
        };

        var ajaxGet = fast ? $.get(this.options.fastUrl + offset) : $.get(this.options.slowUrl + offset);
        if (fast) {
            ajaxGet.fail(function () {
                //switch to slow requests
                fast = false;
                var ajaxGet2 = $.get(this.options.slowUrl + offset + "&switchToSlowAjax=true");
                ajaxGet2.done(onSuccess);
            });
        }
        ajaxGet.done(onSuccess);
    }
});
