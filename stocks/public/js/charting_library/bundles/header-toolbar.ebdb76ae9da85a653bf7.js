(window.webpackJsonp = window.webpackJsonp || []).push([
    ["header-toolbar"], {
        "+GaQ": function(e, t, a) {
            "use strict";
            a.d(t, "a", (function() {
                return i
            }));
            var s = a("q1tI");

            function i(e) {
                if (e.map) {
                    return s.Children.toArray(e.children).map(e.map)
                }
                return e.children
            }
        },
        "1TxM": function(e, t, a) {
            "use strict";
            a.d(t, "c", (function() {
                return l
            })), a.d(t, "a", (function() {
                return c
            })), a.d(t, "b", (function() {
                return d
            }));
            var s = a("q1tI"),
                i = a.n(s),
                n = a("17x9"),
                r = a.n(n);
            const o = i.a.createContext({});

            function l(e, t) {
                r.a.checkPropTypes(t, e, "context", "RegistryContext")
            }

            function c(e) {
                const {
                    validation: t,
                    value: a
                } = e;
                return l(a, t), i.a.createElement(o.Provider, {
                    value: a
                }, e.children)
            }

            function d() {
                return o
            }
        },
        "6aN0": function(e, t, a) {
            e.exports = {
                "css-value-header-toolbar-height": "38px",
                toolbar: "toolbar-LZaMRgb9",
                isHidden: "isHidden-LZaMRgb9",
                overflowWrap: "overflowWrap-LZaMRgb9",
                customButton: "customButton-LZaMRgb9",
                hovered: "hovered-LZaMRgb9"
            }
        },
        "6oLA": function(e, t) {
            e.exports = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"><path fill="currentColor" fill-rule="evenodd" d="M4.56 14a10.05 10.05 0 00.52.91c.41.69 1.04 1.6 1.85 2.5C8.58 19.25 10.95 21 14 21c3.05 0 5.42-1.76 7.07-3.58A17.18 17.18 0 0023.44 14a9.47 9.47 0 00-.52-.91c-.41-.69-1.04-1.6-1.85-2.5C19.42 8.75 17.05 7 14 7c-3.05 0-5.42 1.76-7.07 3.58A17.18 17.18 0 004.56 14zM24 14l.45-.21-.01-.03a7.03 7.03 0 00-.16-.32c-.11-.2-.28-.51-.5-.87-.44-.72-1.1-1.69-1.97-2.65C20.08 7.99 17.45 6 14 6c-3.45 0-6.08 2-7.8 3.92a18.18 18.18 0 00-2.64 3.84v.02h-.01L4 14l-.45-.21-.1.21.1.21L4 14l-.45.21.01.03a5.85 5.85 0 00.16.32c.11.2.28.51.5.87.44.72 1.1 1.69 1.97 2.65C7.92 20.01 10.55 22 14 22c3.45 0 6.08-2 7.8-3.92a18.18 18.18 0 002.64-3.84v-.02h.01L24 14zm0 0l.45.21.1-.21-.1-.21L24 14zm-10-3a3 3 0 100 6 3 3 0 000-6zm-4 3a4 4 0 118 0 4 4 0 01-8 0z"/></svg>'
        },
        "8d0Q": function(e, t, a) {
            "use strict";
            a.d(t, "b", (function() {
                return i
            })), a.d(t, "a", (function() {
                return n
            }));
            var s = a("q1tI");

            function i() {
                const [e, t] = Object(s.useState)(!1);
                return [e, {
                    onMouseOver: function(e) {
                        n(e) && t(!0)
                    },
                    onMouseOut: function(e) {
                        n(e) && t(!1)
                    }
                }]
            }

            function n(e) {
                return !e.currentTarget.contains(e.relatedTarget)
            }
        },
        Iivm: function(e, t, a) {
            "use strict";
            var s = a("mrSG"),
                i = a("q1tI");
            const n = i.forwardRef((e, t) => {
                const {
                    icon: a = ""
                } = e, n = Object(s.a)(e, ["icon"]);
                return i.createElement("span", Object.assign({}, n, {
                    ref: t,
                    dangerouslySetInnerHTML: {
                        __html: a
                    }
                }))
            });
            a.d(t, "a", (function() {
                return n
            }))
        },
        KMbc: function(e, t, a) {
            "use strict";
            a.r(t);
            var s = a("q1tI"),
                i = a("i8i4"),
                n = a("Eyy1"),
                r = a("mrSG"),
                o = (a("P5fv"), a("TSYQ")),
                l = a("4O8T"),
                c = a.n(l),
                d = a("UXvI"),
                h = a("Kxc7"),
                u = a("FQhm"),
                v = a("17x9"),
                m = a("cvc5"),
                p = (a("EsMY"), a("+GaQ")),
                b = a("+GxX"),
                g = a("KrBX");

            function f(e) {
                const {
                    children: t,
                    className: a,
                    noLeftDecoration: i,
                    noRightDecoration: n,
                    noMinimalWidth: r,
                    onClick: l
                } = e;
                return s.createElement("div", {
                    className: o(a, g.group, {
                        [g.noMinimalWidth]: r,
                        [g.noLeftDecoration]: i,
                        [g.noRightDecoration]: n
                    }),
                    onClick: l
                }, t)
            }
            var y = a("tO+E");
            class S extends s.PureComponent {
                constructor() {
                    super(...arguments), this._handleMeasure = ({
                        width: e
                    }) => {
                        this.props.onWidthChange(e)
                    }
                }
                render() {
                    const {
                        children: e,
                        shouldMeasure: t
                    } = this.props;
                    return s.createElement(m, {
                        shouldMeasure: t,
                        onMeasure: this._handleMeasure,
                        whitelist: ["width"]
                    }, s.createElement("div", {
                        className: y.wrap
                    }, e))
                }
            }
            var _ = a("tU7i"),
                C = a("Opoj");

            function E(e) {
                return s.createElement(_.b, Object.assign({}, e, {
                    forceInteractive: !0,
                    icon: C
                }))
            }
            a("YFKU");
            var M = a("Iivm"),
                w = a("a+Yp"),
                I = a("6oLA");
            const O = {
                text: window.t("View Only Mode")
            };

            function k(e) {
                return s.createElement("div", {
                    className: w.wrap
                }, s.createElement(M.a, {
                    className: w.icon,
                    icon: I
                }), O.text)
            }
            var x, F = a("4Cm8"),
                R = a("XAms");
            ! function(e) {
                e.SymbolSearch = "header-toolbar-symbol-search", e.Intervals = "header-toolbar-intervals", e.ChartStyles = "header-toolbar-chart-styles", e.Compare = "header-toolbar-compare", e.Indicators = "header-toolbar-indicators", e.StudyTemplates = "header-toolbar-study-templates", e.Alerts = "header-toolbar-alerts", e.Layouts = "header-toolbar-layouts", e.SaveLoad = "header-toolbar-save-load", e.UndoRedo = "header-toolbar-undo-redo", e.Properties = "header-toolbar-properties", e.PublishDesktop = "header-toolbar-publish-desktop", e.PublishMobile = "header-toolbar-publish-mobile", e.Fullscreen = "header-toolbar-fullscreen", e.Screenshot = "header-toolbar-screenshot", e.Replay = "header-toolbar-replay", e.Financials = "header-toolbar-financials", e.StartTrial = "header-toolbar-start-trial"
            }(x || (x = {}));
            var V = a("8d0Q"),
                W = a("1TxM"),
                L = a("a8bL");
            const P = Object(b.isFeatureEnabled)("hide-copy-readonly"),
                T = Object(W.b)();
            class N extends s.PureComponent {
                constructor(e, t) {
                    super(e, t), this._handleMouseOver = e => {
                        Object(V.a)(e) && this.setState({
                            isHovered: !0
                        })
                    }, this._handleMouseOut = e => {
                        Object(V.a)(e) && this.setState({
                            isHovered: !1
                        })
                    }, this._activateSymbolSearchMode = () => {
                        this._setMode(2)
                    }, this._activateNormalMode = () => {
                        this._setMode(1)
                    }, this._handleInnerResize = e => {
                        const {
                            onWidthChange: t
                        } = this.props;
                        t && t(e)
                    }, this._handleMeasureAvailableSpace = ({
                        width: e
                    }) => {
                        const {
                            onAvailableSpaceChange: t
                        } = this.props;
                        t && t(e)
                    }, this._processCustoms = e => {
                        const {
                            isFake: t
                        } = this.props, {
                            mode: a
                        } = this.state, {
                            tools: i
                        } = this.context;
                        return e.map(e => s.createElement(f, {
                            className: o(1 !== a && L.hidden)
                        }, s.createElement(i.Custom, Object.assign({}, e, {
                            isFake: t
                        }))))
                    }, this._fixLastGroup = (e, t, a) => {
                        if (t === a.length - 1 && s.isValidElement(e) && e.type === f) {
                            const t = void 0 !== this.context.tools.Publish && !this.props.readOnly;
                            return s.cloneElement(e, {
                                noRightDecoration: t
                            })
                        }
                        return e
                    }, Object(W.c)(t, {
                        tools: v.any.isRequired
                    }), this.state = {
                        isHovered: !1,
                        mode: 1,
                        isAuthenticated: void 0
                    }
                }
                componentDidMount() {
                    0
                }
                componentWillUnmount() {
                    0
                }
                render() {
                    const {
                        tools: e
                    } = this.context, {
                        features: t,
                        displayMode: a,
                        chartSaver: i,
                        studyMarket: n,
                        readOnly: r,
                        saveLoadSyncEmitter: l,
                        leftCustomButtons: c,
                        rightCustomButtons: d,
                        showScrollbarWhen: h,
                        width: u = 0,
                        isFake: v = !1
                    } = this.props, {
                        isHovered: b,
                        mode: g,
                        isAuthenticated: y
                    } = this.state, _ = this._processCustoms(c), C = this._processCustoms(d), M = h.includes(a);
                    return s.createElement("div", {
                        className: o(L.inner, {
                            [L.fake]: v
                        }),
                        onContextMenu: R.b
                    }, s.createElement(m, {
                        onMeasure: this._handleMeasureAvailableSpace,
                        whitelist: ["width"],
                        shouldMeasure: !v
                    }, s.createElement(F.a, {
                        isVisibleFade: Modernizr.mobiletouch && M,
                        isVisibleButtons: !Modernizr.mobiletouch && M && b,
                        isVisibleScrollbar: !1,
                        shouldMeasure: M && !v,
                        onMouseOver: this._handleMouseOver,
                        onMouseOut: this._handleMouseOut
                    }, s.createElement("div", {
                        className: L.content
                    }, s.createElement(S, {
                        onWidthChange: this._handleInnerResize,
                        shouldMeasure: v
                    }, s.createElement(p.a, {
                        map: this._fixLastGroup
                    }, !r && s.Children.toArray([e.SymbolSearch && s.createElement(f, {
                        key: "symbol",
                        className: 2 === g && L.symbolSearch
                    }, s.createElement(e.SymbolSearch, {
                        id: v ? void 0 : x.SymbolSearch,
                        isActionsVisible: t.allowSymbolSearchSpread,
                        isExpanded: 2 === g,
                        onFocus: this._activateSymbolSearchMode,
                        onBlur: this._activateNormalMode,
                        maxWidth: u
                    })), e.DateRange && s.createElement(f, {
                        key: "range"
                    }, s.createElement(e.DateRange, null)), e.Intervals && 1 === g && s.createElement(f, {
                        key: "intervals"
                    }, s.createElement(e.Intervals, {
                        id: v ? void 0 : x.Intervals,
                        isShownQuicks: t.allowFavoriting,
                        isFavoritingAllowed: t.allowFavoriting,
                        displayMode: a,
                        isFake: v
                    })), e.Bars && 1 === g && s.createElement(f, {
                        key: "styles"
                    }, s.createElement(e.Bars, {
                        id: v ? void 0 : x.ChartStyles,
                        isShownQuicks: t.allowFavoriting,
                        isFavoritingAllowed: t.allowFavoriting,
                        displayMode: a,
                        isFake: v
                    })), e.Compare && 1 === g && s.createElement(f, {
                        key: "compare"
                    }, s.createElement(e.Compare, {
                        id: v ? void 0 : x.Compare,
                        className: L.button,
                        displayMode: a
                    })), e.Indicators && 1 === g && s.createElement(f, {
                        key: "indicators"
                    }, s.createElement(e.Indicators, {
                        id: v ? void 0 : x.Indicators,
                        className: L.button,
                        studyMarket: n,
                        displayMode: a
                    })), e.Financials && 1 === g && s.createElement(f, {
                        key: "financials"
                    }, s.createElement(e.Financials, {
                        id: v ? void 0 : x.Financials,
                        className: L.button,
                        displayMode: a
                    })), e.Templates && 1 === g && s.createElement(f, {
                        key: "templates"
                    }, s.createElement(e.Templates, {
                        id: v ? void 0 : x.StudyTemplates,
                        isShownQuicks: t.allowFavoriting,
                        isFavoritingAllowed: t.allowFavoriting,
                        displayMode: a
                    })), 1 === g && e.Alert && s.createElement(f, {
                        key: "alert"
                    }, s.createElement(e.Alert, {
                        id: v ? void 0 : x.Alerts,
                        className: L.button,
                        displayMode: a
                    })), 1 === g && e.AlertReferral && s.createElement(f, {
                        key: "alert-referral"
                    }, s.createElement(e.AlertReferral, {
                        className: L.button,
                        displayMode: a
                    })), e.Replay && 1 === g && s.createElement(f, {
                        key: "replay"
                    }, s.createElement(e.Replay, {
                        id: v ? void 0 : x.Replay,
                        className: L.button,
                        displayMode: a
                    })), e.UndoRedo && 1 === g && s.createElement(f, {
                        key: "undo-redo"
                    }, s.createElement(e.UndoRedo, {
                        id: v ? void 0 : x.UndoRedo
					})), this._isSaveLoadVisible() && e.SaveLoad && s.createElement(f, {
                        key: "save-load-right"
                    }, s.createElement(e.SaveLoad, {
                        id: v ? void 0 : x.SaveLoad,
                        chartSaver: i,
                        isReadOnly: r,
                        displayMode: a,
                        isFake: v,
                        stateSyncEmitter: l
                    })), e.ScalePercentage && s.createElement(f, {
                        key: "percentage"
                    }, s.createElement(e.ScalePercentage, null)), e.ScaleLogarithm && s.createElement(f, {
                        key: "logarithm"
                    }, s.createElement(e.ScaleLogarithm, null)), ..._]), 1 === g ? function(e) {
                        const t = e.findIndex(e => s.isValidElement(e) && !!e.key && -1 !== e.key.toString().indexOf("view-only-badge"));
                        return [t].filter(e => e >= 0).forEach(t => {
                            e = s.Children.map(e, (e, a) => {
                                if (s.isValidElement(e)) {
                                    switch ([t - 1, t, t + 1].indexOf(a)) {
                                        case 0:
                                            const t = {
                                                noRightDecoration: !0
                                            };
                                            e = s.cloneElement(e, t);
                                            break;
                                        case 1:
                                            const a = {
                                                noLeftDecoration: !0,
                                                noRightDecoration: !0
                                            };
                                            e = s.cloneElement(e, a);
                                            break;
                                        case 2:
                                            const i = {
                                                noLeftDecoration: !0
                                            };
                                            e = s.cloneElement(e, i)
                                    }
                                }
                                return e
                            })
                        }), e
                    }(s.Children.toArray([r && s.createElement(f, {
                        key: "view-only-badge"
                    }, s.createElement(k, null)), s.createElement(f, {
                        key: "gap",
                        className: o(L.fill, v && L.collapse)
                    }), !r && e.Layout && s.createElement(f, {
                        key: "layout"
                    }, s.createElement(e.Layout, {
                        id: v ? void 0 : x.Layouts                    
                    })), e.SaveLoadReferral && s.createElement(f, {
                        key: "save-load-referral"
                    }, s.createElement(e.SaveLoadReferral, {
                        isReadOnly: r,
                        displayMode: a
                    })), t.showLaunchInPopupButton && e.OpenPopup && s.createElement(f, {
                        key: "popup"
                    }, s.createElement(e.OpenPopup, null)), !r && e.Properties && s.createElement(f, {
                        key: "properties"
                    }, s.createElement(e.Properties, {
                        id: v ? void 0 : x.Properties,
                        className: L.iconButton
                    })), !r && e.Fullscreen && s.createElement(f, {
                        key: "fullscreen",
                        onClick: this._trackFullscreenButtonClick
                    }, s.createElement(e.Fullscreen, {
                        id: v ? void 0 : x.Fullscreen
                    })), e.Screenshot && s.createElement(f, {
                        key: "screenshot"
                    }, s.createElement(e.Screenshot, {
                        id: v ? void 0 : x.Screenshot,
                        className: L.iconButton
                    })), !r && e.Publish && s.createElement(f, {
                        key: "publish",
                        className: L.mobilePublish
                    }, s.createElement(e.Publish, {
                        id: v ? void 0 : x.PublishMobile
                    })), ...C])) : [s.createElement(f, {
                        key: "gap",
                        className: o(L.fill, 2 === g && L.minimalPriority)
                    }), s.createElement(f, {
                        key: "symbol-search-close"
                    }, s.createElement(E, {
                        className: o(L.iconButton, L.symbolSearchClose)
                    }))]))))), e.Publish && !r && !v && s.createElement(e.Publish, {
                        id: x.PublishDesktop,
                        className: L.desktopPublish
                    }))
                }
                _onLoginStateChange() {
                    0
                }
                _setMode(e) {
                    this.setState({
                        mode: e
                    })
                }
                _trackFullscreenButtonClick() {
                    0
                }
                _isSaveLoadVisible() {
                    const {
                        readOnly: e
                    } = this.props;
                    return !(e && P)
                }
            }
            N.contextType = T;
            var B = a("hY0g"),
                A = a.n(B),
                j = a("ulZB");
            class z extends j.b {
                constructor(e, t, a = []) {
                    super(e, t, "FAVORITE_CHART_STYLES_CHANGED", "StyleWidget.quicks", a)
                }
            }
            var D = a("pPtI"),
                K = a("IVMC"),
                H = a.n(K);
            class X extends j.a {
                constructor(e, t, a) {
                    super(e, t, "FAVORITE_INTERVALS_CHANGED", "IntervalWidget.quicks", a)
                }
                _serialize(e) {
                    return H()(e.map(D.normalizeIntervalString))
                }
                _deserialize(e) {
                    return H()(Object(D.convertResolutionsFromSettings)(e).filter(D.isResolutionMultiplierValid).map(D.normalizeIntervalString))
                }
            }
            var G = a("Vdly"),
                U = a("FBuY");
            a("bSeV");
            class Q extends j.a {
                constructor(e, t, a = []) {
                    super(e, t, "CUSTOM_INTERVALS_CHANGED", "IntervalWidget.intervals", a)
                }
                set(e, t) {
                    e.length, this.get().length, super.set(e, t)
                }
                _serialize(e) {
                    return H()(e.map(D.normalizeIntervalString))
                }
                _deserialize(e) {
                    return H()(Object(D.convertResolutionsFromSettings)(e).filter(D.isResolutionMultiplierValid).map(D.normalizeIntervalString))
                }
            }
            const Y = new Q(U.TVXWindowEvents, G);
            var q = a("LxhU"),
                Z = a("cSDC");
            class J {
                constructor(e) {
                    this._customIntervalsService = Y, this._chartApiInstance = e
                }
                getDefaultIntervals() {
                    return null === this._chartApiInstance ? [] : this._chartApiInstance.defaultResolutions().map(D.normalizeIntervalString)
                }
                getCustomIntervals() {
                    return this._customIntervalsService.get()
                }
                add(e, t, a) {
                    if (!this.isValidInterval(e, t)) return null;
                    const s = this._getIntervalString(e, t),
                        i = Object(D.normalizeIntervalString)(s),
                        n = this.getCustomIntervals();
                    return this._isIntervalDefault(i) || n.includes(i) ? null : (this._customIntervalsService.set(Object(D.sortResolutions)([...n, i])), i)
                }
                remove(e) {
                    this._customIntervalsService.set(this.getCustomIntervals().filter(t => t !== e))
                }
                isValidInterval(e, t) {
                    const a = parseInt(e);
                    return a === this._minMaxTime(a, t)
                }
                getOnChange() {
                    return this._customIntervalsService.getOnChange()
                }
                getPossibleIntervals() {
                    return Z.a
                }
                getResolutionUtils() {
                    return {
                        getMaxResolutionValue: this._getMaxResolutionValue,
                        getTranslatedResolutionModel: D.getTranslatedResolutionModel,
                        mergeResolutions: D.mergeResolutions,
                        sortResolutions: D.sortResolutions
                    }
                }
                _getMaxResolutionValue(e) {
                    return q.Interval.isMinuteHours(e) ? Math.floor(Object(D.getMaxResolutionValue)("1") / 60) : Object(D.getMaxResolutionValue)(e)
                }
                _isIntervalDefault(e) {
                    return this.getDefaultIntervals().includes(e)
                }
                _minMaxTime(e, t) {
                    return Math.max(1, Math.min(e, this._getMaxResolutionValue(t)))
                }
                _getIntervalString(e, t) {
                    const a = parseInt(e),
                        s = q.Interval.parse(t),
                        i = s.isMinuteHours() ? 60 * a : a;
                    return new q.Interval(s.kind(), i).value()
                }
            }
            var ee = a("yMne"),
                te = a("cBZt"),
                ae = a("TcSq"),
                se = a("aIyQ"),
                ie = a.n(se);
            const ne = {};
            let re = null;
            class oe {
                constructor(e = G) {
                    this._favorites = [], this._favoritesChanged = new ie.a, this._settings = e, U.TVXWindowEvents.on("StudyFavoritesChanged", e => {
                        const t = JSON.parse(e);
                        this._loadFromState(t.favorites || [])
                    }), this._settings.onSync.subscribe(this, this._loadFavs), this._loadFavs()
                }
                isFav(e) {
                    const t = this.favId(e);
                    return -1 !== this._findFavIndex(t)
                }
                toggleFavorite(e) {
                    this.isFav(e) ? this.removeFavorite(e) : this.addFavorite(e)
                }
                addFavorite(e) {
                    const t = this.favId(e);
                    this._favorites.push(ce(t)), this._favoritesChanged.fire(), this._saveFavs()
                }
                removeFavorite(e) {
                    const t = this.favId(e),
                        a = this._findFavIndex(t); - 1 !== a && (this._favorites.splice(a, 1), this._favoritesChanged.fire()), this._saveFavs()
                }
                favId(e) {
                    return Object(ae.isPineIdString)(e) ? e : Object(ae.extractPineId)(e) || Object(te.extractStudyId)(e)
                }
                favorites() {
                    return this._favorites
                }
                favoritePineIds() {
                    return this._favorites.filter(e => "pine" === e.type).map(e => e.pineId)
                }
                favoritesChanged() {
                    return this._favoritesChanged
                }
                static getInstance() {
                    return null === re && (re = new oe), re
                }
                static create(e) {
                    return new oe(e)
                }
                _loadFavs() {
                    const e = this._settings.getJSON("studyMarket.favorites", []);
                    this._loadFromState(e)
                }
                _saveFavs() {
                    const e = this._stateToSave();
                    this._settings.setJSON("studyMarket.favorites", e), U.TVXWindowEvents.emit("StudyFavoritesChanged", JSON.stringify({
                        favorites: e
                    }))
                }
                _stateToSave() {
                    return this._favorites.map(le)
                }
                _loadFromState(e) {
                    this._favorites = e.map(e => ce(function(e) {
                        return e in ne ? ne[e] : e
                    }(e))), this._favoritesChanged.fire()
                }
                _findFavIndex(e) {
                    return this._favorites.findIndex(t => e === le(t))
                }
            }

            function le(e) {
                return "java" === e.type ? e.studyId : e.pineId
            }

            function ce(e) {
                return Object(ae.isPineIdString)(e) ? {
                    type: "pine",
                    pineId: e
                } : {
                    type: "java",
                    studyId: e
                }
            }
            const de = {
                [q.ResolutionKind.Ticks]: !1,
                [q.ResolutionKind.Seconds]: !1,
                [q.ResolutionKind.Minutes]: !1,
                [q.SpecialResolutionKind.Hours]: !1,
                [q.ResolutionKind.Days]: !1,
                [q.ResolutionKind.Range]: !1
            };
            class he extends j.b {
                constructor(e, t, a = de) {
                    super(e, t, "INTERVALS_MENU_VIEW_STATE_CHANGED", "IntervalWidget.menu.viewState", a)
                }
                isAllowed(e) {
                    return Object.keys(de).includes(e)
                }
            }
            j.b;
            var ue = a("54XG");
            const ve = {
                    Area: 3,
                    Bars: 0,
                    Candles: 1,
                    "Heiken Ashi": 8,
                    "Hollow Candles": 9,
                    Line: 2
                },
                me = ["1", "30", "60"];

            function pe(e = []) {
                let t = e.map(e => ve[e]) || [1, 4, 5, 6];
                return h.enabled("widget") && (t = [0, 1, 3]), t
            }

            function be(e = []) {
                return Object(D.mergeResolutions)(e, h.enabled("star_some_intervals_by_default") ? me : [])
            }
            new X(U.TVXWindowEvents, G, be()), new z(U.TVXWindowEvents, G, pe()), new ue.FavoriteStudyTemplateService(U.TVXWindowEvents, G);
            const ge = {
                tools: v.any.isRequired,
                isFundamental: v.any,
                chartApiInstance: v.any,
                availableTimeFrames: v.any,
                chartWidgetCollection: v.any,
                windowMessageService: v.any,
                favoriteChartStylesService: v.any,
                favoriteIntervalsService: v.any,
                intervalService: v.any,
                favoriteStudyTemplatesService: v.any,
                studyTemplates: v.any,
                chartChangesWatcher: v.any,
                saveChartService: v.any,
                sharingChartService: v.any,
                loadChartService: v.any,
                chartWidget: v.any,
                favoriteScriptsModel: v.any,
                intervalsMenuViewStateService: v.any,
                templatesMenuViewStateService: v.any,
                financialsDialogController: v.any
            };
            var fe = a("6aN0");
            const ye = [];
            class Se extends s.PureComponent {
                constructor(e) {
                    super(e), this._saveLoadSyncEmitter = new c.a, this._handleFullWidthChange = e => {
                        this._fullWidth = e, this.setState({
                            measureValid: !1
                        })
                    }, this._handleFavoritesWidthChange = e => {
                        this._favoritesWidth = e, this.setState({
                            measureValid: !1
                        })
                    }, this._handleCollapseWidthChange = e => {
                        this._collapseWidth = e, this.setState({
                            measureValid: !1
                        })
                    }, this._handleMeasure = e => {
                        this.setState({
                            availableWidth: e,
                            measureValid: !1
                        })
                    };
                    const {
                        tools: t,
                        windowMessageService: a,
                        chartWidgetCollection: s,
                        chartApiInstance: i,
                        availableTimeFrames: r,
                        isFundamental: o,
                        favoriteIntervalsService: l,
                        favoriteChartStylesService: u,
                        favoriteStudyTemplatesService: v,
                        studyTemplates: m,
                        saveChartService: p,
                        sharingChartService: b,
                        loadChartService: g,
                        financialsDialogController: f
                    } = e;
                    this._showScrollbarWhen = Object(n.ensureDefined)(e.allowedModes).slice(-1), this._panelWidthChangeHandlers = {
                        full: this._handleFullWidthChange,
                        medium: this._handleFavoritesWidthChange,
                        small: this._handleCollapseWidthChange
                    };
                    const {
                        chartChangesWatcher: y
                    } = e;
                    this._chartChangesWatcher = y;
                    const S = pe(this.props.defaultFavoriteStyles);
                    this._favoriteChartStylesService = u || new z(U.TVXWindowEvents, G, S);
                    const _ = be(this.props.defaultFavoriteIntervals);
                    this._favoriteIntervalsService = l || new X(U.TVXWindowEvents, G, _), this._intervalsMenuViewStateService = new he(U.TVXWindowEvents, G), this._intervalService = new J(i), this._registry = {
                        tools: t,
                        isFundamental: o,
                        chartWidgetCollection: s,
                        windowMessageService: a,
                        chartApiInstance: i,
                        availableTimeFrames: r,
                        favoriteStudyTemplatesService: v,
                        studyTemplates: m,
                        saveChartService: p,
                        sharingChartService: b,
                        loadChartService: g,
                        intervalsMenuViewStateService: this._intervalsMenuViewStateService,
                        favoriteChartStylesService: this._favoriteChartStylesService,
                        favoriteIntervalsService: this._favoriteIntervalsService,
                        intervalService: this._intervalService,
                        chartChangesWatcher: this._chartChangesWatcher,
                        chartWidget: s.activeChartWidget.value(),
                        favoriteScriptsModel: oe.getInstance(),
                        templatesMenuViewStateService: this._templatesMenuVuewStateService,
                        financialsDialogController: f
                    }, this.state = {
                        isVisible: !0,
                        availableWidth: 0,
                        displayMode: "full",
                        measureValid: !1,
                        leftCustomButtons: [],
                        rightCustomButtons: []
                    }, this._readOnly = s.readOnly(), this._features = {
                        allowFavoriting: h.enabled("items_favoriting"),
                        showIdeasButton: Boolean(this.props.ideas),
                        showLaunchInPopupButton: Boolean(this.props.popupButton),
                        allowSymbolSearchSpread: h.enabled("header_symbol_search") && h.enabled("show_spread_operators"),
                        allowToolbarHiding: h.enabled("collapsible_header")
                    }, this._setDisplayMode = Object(d.default)(this._setDisplayMode, 100), this._negotiateResizer()
                }
                componentDidUpdate(e, t) {
                    const {
                        isVisible: a,
                        measureValid: s
                    } = this.state;
                    a !== t.isVisible && (u.emit("toggle_header", a), this._negotiateResizer()), s || this._setDisplayMode()
                }
                render() {
                    const e = this.props,
                        {
                            resizerBridge: t,
                            allowedModes: a
                        } = e,
                        i = Object(r.a)(e, ["resizerBridge", "allowedModes"]),
                        {
                            displayMode: l,
                            availableWidth: c,
                            isVisible: d,
                            leftCustomButtons: h,
                            rightCustomButtons: u
                        } = this.state,
                        v = Object.assign({
                            features: this._features,
                            readOnly: this._readOnly,
                            isFake: !1,
                            saveLoadSyncEmitter: this._saveLoadSyncEmitter,
                            width: c,
                            leftCustomButtons: h,
                            rightCustomButtons: u
                        }, i),
                        m = Object.assign(Object.assign({}, v), {
                            isFake: !0,
                            showScrollbarWhen: ye
                        }),
                        p = Object(n.ensureDefined)(a),
                        b = this.props.tools.PublishButtonManager || s.Fragment;
                    return s.createElement(W.a, {
                        value: this._registry,
                        validation: ge
                    }, s.createElement(b, null, s.createElement("div", {
                        className: o(fe.toolbar, {
                            [fe.isHidden]: !d
                        }),
                        onClick: this.props.onClick
                    }, s.createElement("div", {
                        className: fe.overflowWrap
                    }, s.createElement(N, Object.assign({
                        key: "live",
                        showScrollbarWhen: this._showScrollbarWhen,
                        displayMode: l,
                        onAvailableSpaceChange: this._handleMeasure
                    }, v)), p.map(e => s.createElement(N, Object.assign({
                        key: e,
                        displayMode: e,
                        onWidthChange: this._panelWidthChangeHandlers[e]
                    }, m)))))))
                }
                addButton(e = "left") {
                    const t = new A.a(0),
                        a = $(`<div class="apply-common-tooltip ${fe.customButton}">`)[0],
                        s = {
                            key: Number(new Date),
                            element: a,
                            width: t
                        },
                        {
                            leftCustomButtons: i,
                            rightCustomButtons: n
                        } = this.state;
                    return "left" === e ? this.setState({
                        leftCustomButtons: [...i, s]
                    }) : this.setState({
                        rightCustomButtons: [...n, s]
                    }), a
                }
                _negotiateResizer() {
                    this.props.resizerBridge.negotiateHeight(this.state.isVisible ? ee.b : ee.a)
                }
                _setDisplayMode() {
                    const {
                        availableWidth: e
                    } = this.state, {
                        allowedModes: t
                    } = this.props, a = {
                        full: this._fullWidth,
                        medium: this._favoritesWidth,
                        small: this._collapseWidth
                    }, s = Object(n.ensureDefined)(t);
                    let i = s.map(e => a[e]).findIndex(t => e >= t); - 1 === i && (i = s.length - 1);
                    const r = s[i];
                    this.setState({
                        measureValid: !0,
                        displayMode: r
                    })
                }
            }
            Se.defaultProps = {
                allowedModes: ["full", "medium"]
            }, a.d(t, "HeaderToolbarRenderer", (function() {
                return _e
            }));
            class _e {
                constructor(e, t) {
                    this._component = null, this._handleRef = e => {
                        this._component = e
                    }, this._container = e, i.render(s.createElement(Se, Object.assign({}, t, {
                        ref: this._handleRef
                    })), this._container)
                }
                destroy() {
                    i.unmountComponentAtNode(this._container)
                }
                getComponent() {
                    return Object(n.ensureNotNull)(this._component)
                }
            }
        },
        KrBX: function(e, t, a) {
            e.exports = {
                group: "group-3uonVBsm",
                noLeftDecoration: "noLeftDecoration-3uonVBsm",
                noRightDecoration: "noRightDecoration-3uonVBsm",
                noMinimalWidth: "noMinimalWidth-3uonVBsm"
            }
        },
        Opoj: function(e, t) {
            e.exports = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 17" width="17" height="17"><path fill="none" stroke="currentColor" d="M1 1l15 15M1 16L16 1"/></svg>'
        },
        "a+Yp": function(e, t, a) {
            e.exports = {
                wrap: "wrap-35jKyg6w",
                icon: "icon-35jKyg6w"
            }
        },
        a8bL: function(e, t, a) {
            e.exports = {
                "css-value-header-toolbar-height": "38px",
                inner: "inner-pzOKvpP8",
                fake: "fake-pzOKvpP8",
                fill: "fill-pzOKvpP8",
                minimalPriority: "minimalPriority-pzOKvpP8",
                collapse: "collapse-pzOKvpP8",
                button: "button-pzOKvpP8",
                iconButton: "iconButton-pzOKvpP8",
                hidden: "hidden-pzOKvpP8",
                symbolSearch: "symbolSearch-pzOKvpP8",
                symbolSearchClose: "symbolSearchClose-pzOKvpP8",
                content: "content-pzOKvpP8",
                desktopPublish: "desktopPublish-pzOKvpP8",
                mobilePublish: "mobilePublish-pzOKvpP8"
            }
        },
        bQ7Y: function(e, t, a) {
            e.exports = {
                button: "button-2Vpz_LXc",
                hover: "hover-2Vpz_LXc",
                isInteractive: "isInteractive-2Vpz_LXc",
                isGrouped: "isGrouped-2Vpz_LXc",
                isActive: "isActive-2Vpz_LXc",
                isOpened: "isOpened-2Vpz_LXc",
                isDisabled: "isDisabled-2Vpz_LXc",
                text: "text-2Vpz_LXc",
                icon: "icon-2Vpz_LXc"
            }
        },
        cSDC: function(e, t, a) {
            "use strict";
            a.d(t, "a", (function() {
                return i
            }));
            var s = a("YFKU");
            const i = [{
                name: "1",
                label: Object(s.t)("minutes", {
                    context: "interval"
                })
            }, {
                name: "1H",
                label: Object(s.t)("hours", {
                    context: "interval"
                })
            }, {
                name: "1D",
                label: Object(s.t)("days", {
                    context: "interval"
                })
            }, {
                name: "1W",
                label: Object(s.t)("weeks", {
                    context: "interval"
                })
            }, {
                name: "1M",
                label: Object(s.t)("months", {
                    context: "interval"
                })
            }]
        },
        "tO+E": function(e, t, a) {
            e.exports = {
                "css-value-header-toolbar-height": "38px",
                wrap: "wrap-1ETeWwz2"
            }
        },
        tU7i: function(e, t, a) {
            "use strict";
            a.d(t, "a", (function() {
                return l
            })), a.d(t, "b", (function() {
                return c
            }));
            var s = a("mrSG"),
                i = a("q1tI"),
                n = a("TSYQ"),
                r = a("Iivm"),
                o = a("bQ7Y");
            const l = o,
                c = i.forwardRef((e, t) => {
                    const {
                        icon: a,
                        isActive: l,
                        isOpened: c,
                        isDisabled: d,
                        isGrouped: h,
                        isHovered: u,
                        onClick: v,
                        text: m,
                        textBeforeIcon: p,
                        title: b,
                        theme: g = o,
                        className: f,
                        forceInteractive: y,
                        "data-name": S
                    } = e, _ = Object(s.a)(e, ["icon", "isActive", "isOpened", "isDisabled", "isGrouped", "isHovered", "onClick", "text", "textBeforeIcon", "title", "theme", "className", "forceInteractive", "data-name"]), C = n(f, g.button, b && "apply-common-tooltip", {
                        [g.isActive]: l,
                        [g.isOpened]: c,
                        [g.isInteractive]: (y || Boolean(v)) && !d,
                        [g.isDisabled]: d,
                        [g.isGrouped]: h,
                        [g.hover]: u
                    }), E = a && ("string" == typeof a ? i.createElement(r.a, {
                        className: g.icon,
                        icon: a
                    }) : i.cloneElement(a, {
                        className: n(g.icon, a.props.className)
                    }));
                    return i.createElement("div", Object.assign({}, _, {
                        ref: t,
                        "data-role": "button",
                        className: C,
                        onClick: d ? void 0 : v,
                        title: b,
                        "data-name": S
                    }), p && m && i.createElement("div", {
                        className: n("js-button-text", g.text)
                    }, m), E, !p && m && i.createElement("div", {
                        className: n("js-button-text", g.text)
                    }, m))
                })
        }
    }
]);