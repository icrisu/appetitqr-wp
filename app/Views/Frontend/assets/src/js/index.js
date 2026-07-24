/**
 * AppetitQR menu — frontend enhancements.
 *
 * The menu itself is already rendered by PHP; nothing here is required to read it.
 * This script layers on the interactions the server cannot do: search, category
 * scroll-spy, the product popup and the cart.
 *
 * Written as a dependency-free IIFE (Fuse.js aside) so it can be enqueued directly
 * with no build step, and scoped per shortcode instance so several menus can share a
 * page without touching each other's state.
 */
(function () {
    'use strict';

    /** Strings the script generates itself, translated via wp_localize_script. */
    var LABELS = (typeof window.AppetitQRMenu !== 'undefined' && window.AppetitQRMenu.labels) || {};

    /** Matches the weights used by the diner-facing menu (SEARCH_KEYS in MenuClient.tsx). */
    var FUSE_OPTIONS = {
        includeScore: false,
        threshold: 0.35,
        ignoreLocation: true,
        keys: [
            { name: 'title', weight: 2 },
            { name: 'description', weight: 1 }
        ]
    };

    function parseJson(raw, fallback) {
        if (!raw) return fallback;
        try {
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments;
            var self = this;
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                fn.apply(self, args);
            }, wait);
        };
    }

    /* ---------------------------------------------------------------------
     * Cart / list store — a port of useMyList (_templates/default/hooks/useMyList.ts).
     *
     * One bucket per location slug holds every list as
     * { name: { name, createdAt, items[] } }. Order mode owns the fixed `__cart__`
     * entry; dine-in owns timestamped ones and keeps them as history. Both modes
     * share the bucket without leaking into each other, exactly as in the app.
     * ------------------------------------------------------------------- */

    var SINGLE_CART_NAME = '__cart__';

    function generateListName() {
        var now = new Date();
        var pad = function (n) { return String(n).padStart ? String(n).padStart(2, '0') : (n < 10 ? '0' + n : '' + n); };
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) +
            ' - ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
    }

    function Cart(root, config, currency) {
        this.root = root;
        this.config = config || {};
        this.currency = currency || { symbol: '', position: 'prefix', decimals: 2 };
        this.isDineIn = this.config.mode === 'dinein';
        this.storageKey = 'appetitqr_wp_lists_' + (root.getAttribute('data-apq-slug') || 'default');

        // Order mode reuses one fixed list so no history accumulates; dine-in mints a
        // fresh name on every page load. Held in memory only — never read back from
        // storage, so a reload always starts a new list.
        this.sessionListName = this.isDineIn ? generateListName() : SINGLE_CART_NAME;

        this.lists = this.load();
    }

    Cart.prototype.belongsToMode = function (name) {
        return this.isDineIn ? name !== SINGLE_CART_NAME : name === SINGLE_CART_NAME;
    };

    Cart.prototype.readRaw = function () {
        try {
            var parsed = parseJson(window.localStorage.getItem(this.storageKey), {});
            return (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) ? parsed : {};
        } catch (e) {
            // Private mode or blocked storage — the list just does not persist.
            return {};
        }
    };

    Cart.prototype.load = function () {
        return this.readRaw();
    };

    /**
     * Drops items whose product or variation is no longer on the page (deleted or
     * hidden in the admin) so counts and totals match what the guest can actually see.
     * Mirrors the reconcile step in useMyList's hydration effect.
     */
    Cart.prototype.prune = function (isValidItem) {
        var changed = false;

        for (var name in this.lists) {
            if (!Object.prototype.hasOwnProperty.call(this.lists, name)) continue;
            var list = this.lists[name];
            if (!list || !Array.isArray(list.items)) continue;

            var kept = list.items.filter(isValidItem);
            if (kept.length !== list.items.length) {
                list.items = kept;
                changed = true;
            }
        }

        if (changed) this.save();
    };

    Cart.prototype.save = function () {
        try {
            window.localStorage.setItem(this.storageKey, JSON.stringify(this.lists));
        } catch (e) {
            /* not fatal */
        }
    };

    /** Lists belonging to the active mode, newest first. */
    Cart.prototype.modeLists = function () {
        var self = this;
        var out = [];

        for (var name in this.lists) {
            if (!Object.prototype.hasOwnProperty.call(this.lists, name)) continue;
            if (self.belongsToMode(name)) out.push(this.lists[name]);
        }

        out.sort(function (a, b) {
            return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
        });

        return out;
    };

    /** The session's list, created on first use. */
    Cart.prototype.ensureList = function () {
        if (!this.sessionListName) {
            this.sessionListName = this.isDineIn ? generateListName() : SINGLE_CART_NAME;
        }

        if (!this.lists[this.sessionListName]) {
            this.lists[this.sessionListName] = {
                name: this.sessionListName,
                createdAt: new Date().toISOString(),
                items: []
            };
        }

        return this.lists[this.sessionListName];
    };

    Cart.prototype.add = function (product, variation) {
        var list = this.ensureList();
        var existing = null;

        for (var i = 0; i < list.items.length; i++) {
            if (list.items[i].productId === product.id && list.items[i].variationId === variation.id) {
                existing = list.items[i];
                break;
            }
        }

        if (existing) {
            existing.quantity += 1;
        } else {
            list.items.push({
                productId: product.id,
                variationId: variation.id,
                productName: product.name,
                variationName: variation.name,
                price: variation.effectivePrice,
                quantity: 1
            });
        }

        this.save();
    };

    Cart.prototype.setQuantity = function (listName, index, quantity) {
        var list = this.lists[listName];
        if (!list || !list.items[index]) return;

        if (quantity <= 0) {
            list.items.splice(index, 1);
        } else {
            list.items[index].quantity = quantity;
        }

        this.save();
    };

    /**
     * Deleting the session's own list clears the remembered name, so the next add
     * starts a fresh one rather than resurrecting the deleted entry.
     */
    Cart.prototype.deleteList = function (name) {
        delete this.lists[name];
        if (this.sessionListName === name) {
            this.sessionListName = null;
        }
        this.save();
    };

    /** Items of the active list — order mode's single cart. */
    Cart.prototype.currentItems = function () {
        var list = this.lists[this.sessionListName];
        return list && Array.isArray(list.items) ? list.items : [];
    };

    /** Badge count: the whole mode in dine-in, the single cart in order mode. */
    Cart.prototype.count = function () {
        var lists = this.isDineIn ? this.modeLists() : [{ items: this.currentItems() }];
        var total = 0;

        lists.forEach(function (list) {
            (list.items || []).forEach(function (item) { total += item.quantity; });
        });

        return total;
    };

    Cart.prototype.listSubtotal = function (items) {
        return (items || []).reduce(function (sum, item) {
            return sum + item.price * item.quantity;
        }, 0);
    };

    Cart.prototype.subtotal = function () {
        return this.listSubtotal(this.currentItems());
    };

    /** Mirrors the order of operations in OrderCartPage: discount, then shipping. */
    Cart.prototype.totals = function () {
        var subtotal = this.subtotal();
        var discountPct = Number(this.config.discountPct) || 0;
        var discount = discountPct > 0 ? Math.round((subtotal * discountPct) / 100) : 0;
        var shipping = Number(this.config.shipping) || 0;
        var total = subtotal - discount + shipping;

        return {
            subtotal: subtotal,
            discount: discount,
            shipping: shipping,
            total: total < 0 ? 0 : total
        };
    };

    Cart.prototype.formatPrice = function (cents) {
        var decimals = Number(this.currency.decimals);
        if (isNaN(decimals)) decimals = 2;

        var value = (Number(cents) / Math.pow(10, decimals)).toFixed(decimals);

        return this.currency.position === 'suffix'
            ? value + ' ' + this.currency.symbol
            : this.currency.symbol + ' ' + value;
    };

    /** Plain-text order summary handed to tel:/wa.me, as the app does. */
    Cart.prototype.buildMessage = function () {
        var self = this;
        var lines = [];

        if (this.config.locationName) {
            lines.push(this.config.locationName);
            lines.push('');
        }

        this.currentItems().forEach(function (item) {
            var name = item.productName;
            if (item.variationName) {
                name += ' (' + item.variationName + ')';
            }
            lines.push(item.quantity + ' x ' + name + ' — ' + self.formatPrice(item.price * item.quantity));
        });

        var totals = this.totals();
        var labels = this.config.labels || {};

        lines.push('');
        lines.push((labels.subtotal || 'Subtotal') + ': ' + this.formatPrice(totals.subtotal));

        if (totals.discount > 0) {
            lines.push((labels.discount || 'Discount') + ': -' + this.formatPrice(totals.discount));
        }
        if (totals.shipping > 0) {
            lines.push((labels.shipping || 'Shipping') + ': ' + this.formatPrice(totals.shipping));
        }

        lines.push((labels.total || 'Total') + ': ' + this.formatPrice(totals.total));

        return lines.join('\n');
    };

    /* ---------------------------------------------------------------------
     * Menu instance
     * ------------------------------------------------------------------- */

    function MenuInstance(root) {
        this.root = root;
        this.cards = Array.prototype.slice.call(root.querySelectorAll('[data-apq-product]'));
        this.sections = Array.prototype.slice.call(root.querySelectorAll('[data-apq-category]'));
        this.navLinks = Array.prototype.slice.call(root.querySelectorAll('[data-apq-category-link]'));
        this.emptyState = root.querySelector('[data-apq-empty]');
        this.currency = parseJson(root.getAttribute('data-apq-currency'), { symbol: '', position: 'prefix', decimals: 2 });

        this.productData = {};
        this.buildProductIndex();

        this.initSearch();
        this.initCategoryNav();
        this.initModal();
        this.initCart();
    }

    MenuInstance.prototype.buildProductIndex = function () {
        var self = this;

        this.searchRecords = this.cards.map(function (card) {
            var id = card.getAttribute('data-apq-product');
            var script = card.querySelector('[data-apq-product-data]');
            var data = parseJson(script && script.textContent, null);

            if (data) {
                self.productData[id] = data;
            }

            return {
                id: id,
                title: (data && data.name) || '',
                description: (data && data.description) || '',
                card: card
            };
        });

        if (typeof window.Fuse === 'function') {
            this.fuse = new window.Fuse(this.searchRecords, FUSE_OPTIONS);
        }
    };

    /* --- Search --------------------------------------------------------- */

    MenuInstance.prototype.initSearch = function () {
        var self = this;
        var input = this.root.querySelector('[data-apq-search]');
        var clearBtn = this.root.querySelector('[data-apq-search-clear]');

        if (!input) return;

        var run = debounce(function () {
            self.applySearch(input.value);
            if (clearBtn) clearBtn.hidden = input.value === '';
        }, 150);

        input.addEventListener('input', run);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                clearBtn.hidden = true;
                self.applySearch('');
                input.focus();
            });
        }
    };

    MenuInstance.prototype.applySearch = function (query) {
        var trimmed = (query || '').trim();

        if (trimmed === '') {
            this.showAllProducts();
            return;
        }

        var matches = {};

        if (this.fuse) {
            this.fuse.search(trimmed).forEach(function (result) {
                matches[result.item.id] = true;
            });
        } else {
            // Fuse failed to load — fall back to a plain substring match rather than
            // leaving search silently dead.
            var needle = trimmed.toLowerCase();
            this.searchRecords.forEach(function (record) {
                if ((record.title + ' ' + record.description).toLowerCase().indexOf(needle) !== -1) {
                    matches[record.id] = true;
                }
            });
        }

        var visibleCount = 0;
        this.cards.forEach(function (card) {
            var isMatch = !!matches[card.getAttribute('data-apq-product')];
            card.hidden = !isMatch;
            if (isMatch) visibleCount++;
        });

        // Hide a category heading whose products all filtered out.
        this.sections.forEach(function (section) {
            var visible = section.querySelectorAll('[data-apq-product]:not([hidden])').length;
            section.hidden = visible === 0;
        });

        if (this.emptyState) {
            this.emptyState.hidden = visibleCount > 0;
        }
    };

    MenuInstance.prototype.showAllProducts = function () {
        this.cards.forEach(function (card) {
            card.hidden = false;
        });
        this.sections.forEach(function (section) {
            section.hidden = false;
        });
        if (this.emptyState) {
            this.emptyState.hidden = true;
        }
    };

    /* --- Category nav --------------------------------------------------- */

    MenuInstance.prototype.initCategoryNav = function () {
        var self = this;

        if (!this.navLinks.length) return;

        this.navLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var id = link.getAttribute('data-apq-category-link');
                var section = self.root.querySelector('[data-apq-category="' + id + '"]');
                if (!section) return;

                // Only take over when smooth scrolling is actually available; otherwise
                // the plain anchor jump is left to do its job.
                if ('scrollBehavior' in document.documentElement.style) {
                    event.preventDefault();
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                self.setActiveLink(id);
            });
        });

        if (!('IntersectionObserver' in window) || !this.sections.length) return;

        var observer = new window.IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        self.setActiveLink(entry.target.getAttribute('data-apq-category'));
                    }
                });
            },
            { rootMargin: '-25% 0px -65% 0px', threshold: 0 }
        );

        this.sections.forEach(function (section) {
            observer.observe(section);
        });
    };

    MenuInstance.prototype.setActiveLink = function (categoryId) {
        this.navLinks.forEach(function (link) {
            if (link.getAttribute('data-apq-category-link') === categoryId) {
                link.classList.add('is-active');
            } else {
                link.classList.remove('is-active');
            }
        });
    };

    /* --- Product modal -------------------------------------------------- */

    MenuInstance.prototype.initModal = function () {
        var self = this;

        this.modal = this.root.querySelector('[data-apq-modal]');
        if (!this.modal) return;

        this.modalEls = {
            media: this.modal.querySelector('[data-apq-modal-media]'),
            title: this.modal.querySelector('[data-apq-modal-title]'),
            description: this.modal.querySelector('[data-apq-modal-description]'),
            variationsWrap: this.modal.querySelector('[data-apq-modal-variations-wrap]'),
            variations: this.modal.querySelector('[data-apq-modal-variations]'),
            allergensWrap: this.modal.querySelector('[data-apq-modal-allergens-wrap]'),
            allergens: this.modal.querySelector('[data-apq-modal-allergens]'),
            dietaryWrap: this.modal.querySelector('[data-apq-modal-dietary-wrap]'),
            dietary: this.modal.querySelector('[data-apq-modal-dietary]'),
            nutritionWrap: this.modal.querySelector('[data-apq-modal-nutrition-wrap]'),
            nutrition: this.modal.querySelector('[data-apq-modal-nutrition]'),
            additionalWrap: this.modal.querySelector('[data-apq-modal-additional-wrap]'),
            additional: this.modal.querySelector('[data-apq-modal-additional]'),
            price: this.modal.querySelector('[data-apq-modal-price]'),
            addBtn: this.modal.querySelector('[data-apq-modal-add]'),
            dialog: this.modal.querySelector('.apq-modal-dialog')
        };

        // The card itself is the trigger, so a click on the add-to-cart button nested
        // inside it would otherwise also open the dialog. That button has its own
        // handler; bail here and let it run alone.
        var cardTriggerFor = function (target) {
            if (!target || !target.closest) return null;
            if (target.closest('[data-apq-add-to-cart]')) return null;

            var trigger = target.closest('[data-apq-open-product]');
            if (!trigger || !self.root.contains(trigger)) return null;

            return trigger.closest('[data-apq-product]');
        };

        this.root.addEventListener('click', function (event) {
            var card = cardTriggerFor(event.target);
            if (!card) return;

            self.openModal(card.getAttribute('data-apq-product'));
        });

        // role="button" elements don't activate on Enter/Space for free, unlike a real
        // <button>. Mirrors the onKeyDown handler on the app's card.
        this.root.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar') return;

            var card = cardTriggerFor(event.target);
            if (!card) return;

            event.preventDefault();
            self.openModal(card.getAttribute('data-apq-product'));
        });

        this.modal.addEventListener('click', function (event) {
            if (event.target.closest && event.target.closest('[data-apq-modal-close]')) {
                self.closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !self.modal.hidden) {
                self.closeModal();
            }
        });

        if (this.modalEls.addBtn) {
            this.modalEls.addBtn.addEventListener('click', function () {
                if (!self.activeProduct || !self.cart) return;

                var variation = self.activeProduct.variations[self.activeVariationIndex];
                if (!variation) return;

                self.cart.add(self.activeProduct, variation);
                self.renderCart();
                self.closeModal();
                self.openCart();
            });
        }
    };

    MenuInstance.prototype.openModal = function (productId) {
        var product = this.productData[productId];
        if (!product || !this.modal) return;

        var els = this.modalEls;

        this.activeProduct = product;
        this.activeVariationIndex = 0;

        els.title.textContent = product.name;
        els.description.textContent = product.description || '';
        els.description.hidden = !product.description;

        els.media.innerHTML = '';
        if (product.image) {
            var img = document.createElement('img');
            img.src = product.image;
            img.alt = product.name;
            img.loading = 'lazy';
            els.media.appendChild(img);
        }

        this.renderModalVariations();
        this.renderChipList(els.allergens, els.allergensWrap, product.allergens);
        this.renderChipList(els.dietary, els.dietaryWrap, product.dietaryTags);
        this.renderNutrition(product.nutrition);

        var hasAdditional = !!product.additionalInfo;
        els.additionalWrap.hidden = !hasAdditional;
        if (hasAdditional) {
            els.additional.textContent = product.additionalInfo;
        }

        if (els.addBtn) {
            els.addBtn.hidden = !(this.cart && product.isAvailable && product.variations.length);
        }

        this.modal.hidden = false;
        document.body.style.overflow = 'hidden';

        if (els.dialog) {
            els.dialog.focus();
        }
    };

    MenuInstance.prototype.renderModalVariations = function () {
        var self = this;
        var els = this.modalEls;
        var variations = this.activeProduct.variations || [];

        els.variations.innerHTML = '';

        // A single variation needs no picker — its price is already in the footer.
        els.variationsWrap.hidden = variations.length < 2;

        variations.forEach(function (variation, index) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'apq-variation' + (index === self.activeVariationIndex ? ' is-selected' : '');
            button.textContent = variation.name + ' · ' + variation.priceLabel;

            button.addEventListener('click', function () {
                self.activeVariationIndex = index;
                self.renderModalVariations();
                self.updateModalPrice();
            });

            els.variations.appendChild(button);
        });

        this.updateModalPrice();
    };

    MenuInstance.prototype.updateModalPrice = function () {
        var variation = this.activeProduct.variations[this.activeVariationIndex];
        this.modalEls.price.textContent = variation ? variation.priceLabel : '';
    };

    MenuInstance.prototype.renderChipList = function (list, wrap, values) {
        if (!list || !wrap) return;

        var items = Array.isArray(values) ? values.filter(function (v) { return typeof v === 'string'; }) : [];

        list.innerHTML = '';
        wrap.hidden = items.length === 0;

        items.forEach(function (value) {
            var li = document.createElement('li');
            li.textContent = value;
            list.appendChild(li);
        });
    };

    /**
     * Renders one chip per value — "🔥 Calories 220kcal" — matching the product page.
     * PHP has already ordered them, translated the labels and appended units, so this
     * only has to lay them out.
     */
    MenuInstance.prototype.renderNutrition = function (entries) {
        var els = this.modalEls;
        if (!els.nutrition || !els.nutritionWrap) return;

        els.nutrition.innerHTML = '';

        var list = Array.isArray(entries) ? entries : [];
        els.nutritionWrap.hidden = list.length === 0;

        list.forEach(function (entry) {
            var li = document.createElement('li');
            var text = entry.label + ' ' + entry.value;

            li.textContent = entry.emoji ? entry.emoji + ' ' + text : text;
            els.nutrition.appendChild(li);
        });
    };

    MenuInstance.prototype.closeModal = function () {
        if (!this.modal) return;
        this.modal.hidden = true;
        document.body.style.overflow = '';
    };

    /* --- Cart ----------------------------------------------------------- */

    MenuInstance.prototype.initCart = function () {
        var self = this;

        // The root carries data-apq-has-cart as the on/off gate; the panel itself is
        // data-apq-cart. Distinct names so a document-wide query for one can never
        // match the other.
        this.cartEl = this.root.querySelector('[data-apq-cart]');
        if (!this.cartEl || this.root.getAttribute('data-apq-has-cart') !== '1') return;

        var config = parseJson(this.cartEl.getAttribute('data-apq-cart-config'), {});
        this.cart = new Cart(this.root, config, this.currency);
        this.isDineIn = this.cart.isDineIn;

        // Reconcile stored lists against what is actually on the page, so a product
        // pulled from the menu in the admin stops counting toward badges and totals.
        this.cart.prune(function (item) {
            var product = self.productData[item.productId];
            if (!product || !product.isAvailable) return false;
            if (!item.variationId) return product.variations.length > 0;
            return product.variations.some(function (v) { return v.id === item.variationId; });
        });

        this.cartEls = {
            items: this.cartEl.querySelector('[data-apq-cart-items]'),
            lists: this.cartEl.querySelector('[data-apq-lists]'),
            empty: this.cartEl.querySelector('[data-apq-cart-empty]'),
            summary: this.cartEl.querySelector('[data-apq-cart-summary]'),
            minWarning: this.cartEl.querySelector('[data-apq-cart-min]'),
            phone: this.cartEl.querySelector('[data-apq-order-phone]'),
            whatsapp: this.cartEl.querySelector('[data-apq-order-whatsapp]'),
            count: this.root.querySelector('[data-apq-cart-count]')
        };

        this.root.addEventListener('click', function (event) {
            var addBtn = event.target.closest ? event.target.closest('[data-apq-add-to-cart]') : null;
            if (addBtn && self.root.contains(addBtn)) {
                var card = addBtn.closest('[data-apq-product]');
                if (card) {
                    var product = self.productData[card.getAttribute('data-apq-product')];
                    if (product && product.variations.length) {
                        // Multi-variation products open the picker instead of silently
                        // guessing which size the diner meant.
                        if (product.variations.length > 1) {
                            self.openModal(product.id);
                            return;
                        }
                        self.cart.add(product, product.variations[0]);
                        self.renderCart();
                        self.openCart();
                    }
                }
                return;
            }

            if (event.target.closest && event.target.closest('[data-apq-open-cart]')) {
                self.openCart();
            }
        });

        this.cartEl.addEventListener('click', function (event) {
            if (event.target.closest && event.target.closest('[data-apq-cart-close]')) {
                self.closeCart();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !self.cartEl.hidden) {
                self.closeCart();
            }
        });

        this.renderCart();
    };

    MenuInstance.prototype.openCart = function () {
        if (!this.cartEl) return;
        this.cartEl.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    MenuInstance.prototype.closeCart = function () {
        if (!this.cartEl) return;
        this.cartEl.hidden = true;
        document.body.style.overflow = '';
    };

    MenuInstance.prototype.renderCart = function () {
        if (!this.cart) return;

        if (this.isDineIn) {
            this.renderLists();
            this.updateCartCount();
            return;
        }

        var self = this;
        var els = this.cartEls;
        var labels = this.cart.config.labels || {};
        var items = this.cart.currentItems();
        var isEmpty = items.length === 0;

        els.items.innerHTML = '';
        els.empty.hidden = !isEmpty;
        els.summary.hidden = isEmpty;

        items.forEach(function (item, index) {
            els.items.appendChild(self.renderCartItem(item, index, self.cart.sessionListName));
        });

        if (!isEmpty) {
            els.summary.innerHTML = '';
            var totals = this.cart.totals();

            els.summary.appendChild(this.summaryRow(labels.subtotal || 'Subtotal', this.cart.formatPrice(totals.subtotal)));

            if (totals.discount > 0) {
                els.summary.appendChild(this.summaryRow(labels.discount || 'Discount', '-' + this.cart.formatPrice(totals.discount)));
            }
            if (this.cart.config.shipping !== undefined) {
                els.summary.appendChild(this.summaryRow(
                    labels.shipping || 'Shipping',
                    totals.shipping > 0 ? this.cart.formatPrice(totals.shipping) : (labels.free || 'FREE')
                ));
            }

            els.summary.appendChild(this.summaryRow(labels.total || 'Total', this.cart.formatPrice(totals.total), true));
        }

        this.updateCartCount();
        this.updateOrderLinks();
    };

    /**
     * Dine-in: one card per stored list, newest first, each with its own estimated
     * total — matching MyListPage.tsx, where the total lives inside the list card
     * rather than being summed across the history.
     */
    MenuInstance.prototype.renderLists = function () {
        var self = this;
        var els = this.cartEls;
        var labels = this.cart.config.labels || {};
        var lists = this.cart.modeLists().filter(function (l) { return l && Array.isArray(l.items); });

        if (!els.lists) return;

        els.lists.innerHTML = '';
        els.empty.hidden = lists.length > 0;

        lists.forEach(function (list) {
            var card = document.createElement('section');
            card.className = 'apq-list-card';

            var header = document.createElement('header');
            header.className = 'apq-list-header';

            var name = document.createElement('span');
            name.className = 'apq-list-name';
            name.textContent = list.name;
            header.appendChild(name);

            if (list.name === self.cart.sessionListName) {
                var badge = document.createElement('span');
                badge.className = 'apq-list-badge';
                badge.textContent = labels.currentList || 'Current';
                header.appendChild(badge);
            }

            var del = document.createElement('button');
            del.type = 'button';
            del.className = 'apq-list-delete';
            del.textContent = '×';
            del.setAttribute('aria-label', labels.deleteList || 'Delete list');
            del.addEventListener('click', function () {
                self.cart.deleteList(list.name);
                self.renderCart();
            });
            header.appendChild(del);

            card.appendChild(header);

            if (list.items.length === 0) {
                var empty = document.createElement('p');
                empty.className = 'apq-list-empty';
                empty.textContent = labels.emptyList || 'Empty list';
                card.appendChild(empty);
            } else {
                list.items.forEach(function (item, index) {
                    card.appendChild(self.renderCartItem(item, index, list.name));
                });

                var footer = document.createElement('div');
                footer.className = 'apq-list-total';

                var label = document.createElement('span');
                label.textContent = labels.total || 'Total (estimated)';

                var value = document.createElement('span');
                value.textContent = self.cart.formatPrice(self.cart.listSubtotal(list.items));

                footer.appendChild(label);
                footer.appendChild(value);
                card.appendChild(footer);
            }

            els.lists.appendChild(card);
        });
    };

    MenuInstance.prototype.renderCartItem = function (item, index, listName) {
        var self = this;

        var row = document.createElement('div');
        row.className = 'apq-cart-item';

        var info = document.createElement('div');
        info.className = 'apq-cart-item-info';

        var name = document.createElement('div');
        name.className = 'apq-cart-item-name';
        name.textContent = item.productName;
        info.appendChild(name);

        if (item.variationName) {
            var variation = document.createElement('div');
            variation.className = 'apq-cart-item-variation';
            variation.textContent = item.variationName;
            info.appendChild(variation);
        }

        var price = document.createElement('div');
        price.className = 'apq-cart-item-variation';
        price.textContent = this.cart.formatPrice(item.price * item.quantity);
        info.appendChild(price);

        var qty = document.createElement('div');
        qty.className = 'apq-cart-qty';

        var minus = document.createElement('button');
        minus.type = 'button';
        minus.textContent = '−';
        minus.setAttribute('aria-label', '-');
        minus.addEventListener('click', function () {
            self.cart.setQuantity(listName, index, item.quantity - 1);
            self.renderCart();
        });

        var value = document.createElement('span');
        value.textContent = String(item.quantity);

        var plus = document.createElement('button');
        plus.type = 'button';
        plus.textContent = '+';
        plus.setAttribute('aria-label', '+');
        plus.addEventListener('click', function () {
            self.cart.setQuantity(listName, index, item.quantity + 1);
            self.renderCart();
        });

        qty.appendChild(minus);
        qty.appendChild(value);
        qty.appendChild(plus);

        row.appendChild(info);
        row.appendChild(qty);

        return row;
    };

    MenuInstance.prototype.summaryRow = function (label, value, isTotal) {
        var row = document.createElement('div');
        row.className = 'apq-cart-summary-row' + (isTotal ? ' is-total' : '');

        var left = document.createElement('span');
        left.textContent = label;

        var right = document.createElement('span');
        right.textContent = value;

        row.appendChild(left);
        row.appendChild(right);

        return row;
    };

    MenuInstance.prototype.updateCartCount = function () {
        if (!this.cartEls.count) return;

        var count = this.cart.count();
        this.cartEls.count.textContent = String(count);
        this.cartEls.count.hidden = count === 0;
    };

    /**
     * Rebuilds the tel:/wa.me targets from the current cart and enforces the location's
     * minimum order value, matching how the app gates checkout.
     */
    MenuInstance.prototype.updateOrderLinks = function () {
        // Dine-in has no ordering path — the panel emits no order links to update.
        if (this.isDineIn) return;

        var els = this.cartEls;
        var totals = this.cart.totals();
        var minOrder = Number(this.cart.config.minOrder) || 0;
        var belowMin = minOrder > 0 && totals.subtotal < minOrder;
        var isEmpty = this.cart.currentItems().length === 0;
        var blocked = isEmpty || belowMin;

        if (els.minWarning) {
            els.minWarning.hidden = !belowMin;
            if (belowMin) {
                var template = LABELS.minimumOrder || 'Minimum order: %s';
                els.minWarning.textContent = template.replace('%s', this.cart.formatPrice(minOrder));
            }
        }

        if (els.whatsapp) {
            var number = this.cart.config.whatsapp;
            els.whatsapp.href = number
                ? 'https://wa.me/' + number.replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(this.cart.buildMessage())
                : '#';
            els.whatsapp.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        }

        if (els.phone) {
            els.phone.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        }
    };

    /* --------------------------------------------------------------------- */

    function init() {
        var roots = document.querySelectorAll('.appetitqr-app[data-apq-instance]');
        Array.prototype.forEach.call(roots, function (root) {
            if (root.getAttribute('data-apq-ready') === '1') return;
            root.setAttribute('data-apq-ready', '1');

            try {
                new MenuInstance(root);
            } catch (error) {
                // A broken enhancement must never take down the server-rendered menu.
                if (window.console && window.console.error) {
                    window.console.error('AppetitQR: initialization error', error);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
