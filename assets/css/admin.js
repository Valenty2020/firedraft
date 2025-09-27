(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize the admin interface
        var FiredraftAdmin = {
            canvas: null,
            
            init: function() {
                this.initCanvas();
                this.initTemplateSelection();
                this.initColorPickers();
                this.initCoverDesigner();
                this.initReportGeneration();
                this.initPDFExport();
            },

            initCanvas: function() {
                if (typeof fabric !== 'undefined') {
                    this.canvas = new fabric.Canvas('cover-canvas', {
                        backgroundColor: '#ffffff'
                    });

                    // Load saved design
                    var savedCover = localStorage.getItem('firedraftCover');
                    if (savedCover) {
                        try {
                            this.canvas.loadFromJSON(savedCover, this.canvas.renderAll.bind(this.canvas));
                        } catch (e) {
                            console.log('Could not load saved cover design');
                        }
                    }

                    // Auto-save cover design
                    var self = this;
                    this.canvas.on('object:modified', function() { self.saveCover(); });
                    this.canvas.on('object:added', function() { self.saveCover(); });
                    this.canvas.on('object:removed', function() { self.saveCover(); });
                }
            },

            initTemplateSelection: function() {
                var self = this;
                $('.firedraft-template-option').on('click', function() {
                    // Remove selected class from all options
                    $('.firedraft-template-option').removeClass('selected');
                    
                    // Add selected class to clicked option
                    $(this).addClass('selected');
                    
                    // Update hidden input
                    var template = $(this).data('template');
                    $('#selected-template').val(template);
                    
                    // Update current template display
                    var templateLabel = firedraftAjax.templates[template] ? firedraftAjax.templates[template].label : template;
                    $('#current-template-name').text(templateLabel);
                });
            },

            initColorPickers: function() {
                var self = this;
                
                $('.wp-color-picker').wpColorPicker({
                    change: function(event, ui) {
                        var id = $(this).attr('id');
                        
                        if (id === 'text-color' && self.canvas) {
                            var activeObj = self.canvas.getActiveObject();
                            if (activeObj && activeObj.type === 'textbox') {
                                activeObj.set({ fill: ui.color.toString() });
                                self.canvas.renderAll();
                            }
                        } else if (id === 'bg-color' && self.canvas) {
                            self.canvas.setBackgroundColor(ui.color.toString(), self.canvas.renderAll.bind(self.canvas));
                        } else if (id === 'subheading-color') {
                            $('.handover-subheading').css('color', ui.color.toString());
                        }
                    }
                });
            },

            initCoverDesigner: function() {
                var self = this;

                // Add Text
                $('#add-text').on('click', function() {
                    if (!self.canvas) return;
                    
                    var textColor = $('#text-color').wpColorPicker('color') || '#000';
                    var text = new fabric.Textbox(firedraftAjax.i18n.edit_text, {
                        left: 50, 
                        top: 50, 
                        fontSize: 24, 
                        fill: textColor,
                        fontFamily: 'Helvetica'
                    });
                    self.canvas.add(text).setActiveObject(text);
                    self.showStatus(firedraftAjax.i18n.text_added, 'success');
                });

                // Add Image
                $('#add-image').on('click', function() {
                    if (!self.canvas) return;
                    
                    var frame = wp.media({ 
                        title: firedraftAjax.i18n.select_image, 
                        multiple: false,
                        library: { type: 'image' }
                    });
                    
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        fabric.Image.fromURL(attachment.url, function(img) {
                            img.scaleToWidth(300);
                            img.set({
                                left: 100,
                                top: 100
                            });
                            self.canvas.add(img).setActiveObject(img);
                            self.showStatus(firedraftAjax.i18n.image_added, 'success');
                        });
                    });
                    
                    frame.open();
                });

                // Clear Cover
                $('#clear-cover').on('click', function() {
                    if (confirm(firedraftAjax.i18n.clear_cover_confirm)) {
                        if (self.canvas) {
                            self.canvas.clear();
                            self.canvas.setBackgroundColor('#ffffff', self.canvas.renderAll.bind(self.canvas));
                        }
                        localStorage.removeItem('firedraftCover');
                        self.showStatus(firedraftAjax.i18n.cover_cleared, 'success');
                    }
                });
            },

            initReportGeneration: function() {
                var self = this;

                // Generate Report
                $('#refresh-report-button').on('click', function() {
                    var $btn = $(this);
                    var originalText = $btn.html();
                    var selectedTemplate = $('#selected-template').val();
                    
                    $btn.html('<span class="dashicons dashicons-update spin"></span> ' + firedraftAjax.i18n.generating);
                    $btn.prop('disabled', true);

                    self.showStatus(firedraftAjax.i18n.generating_report, 'loading');

                    $.ajax({
                        url: firedraftAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'firedraft_generate_report',
                            nonce: firedraftAjax.nonce_generate,
                            template: selectedTemplate
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showStatus(firedraftAjax.i18n.report_generated, 'success');
                                
                                // Reload the page after a short delay
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                self.showStatus(firedraftAjax.i18n.error_generating + ' ' + (response.data || firedraftAjax.i18n.unknown_error), 'error');
                                $btn.html(originalText);
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            self.showStatus(firedraftAjax.i18n.network_error + ' ' + error, 'error');
                            console.error('AJAX Error:', xhr.responseText);
                            $btn.html(originalText);
                            $btn.prop('disabled', false);
                        }
                    });
                });

                // Clear Content
                $('#clear-content-button').on('click', function() {
                    if (confirm(firedraftAjax.i18n.clear_confirm)) {
                        $.ajax({
                            url: firedraftAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'firedraft_clear_report',
                                nonce: firedraftAjax.nonce_clear
                            },
                            success: function(response) {
                                if (response.success) {
                                    self.showStatus(firedraftAjax.i18n.content_cleared, 'success');
                                    
                                    // Reload the page after a short delay
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                }
                            }
                        });
                    }
                });
            },

            initPDFExport: function() {
                var self = this;

                $('#export-to-pdf').on('click', function() {
                    var $btn = $(this);
                    var originalText = $btn.html();
                    $btn.html('<span class="dashicons dashicons-update spin"></span> ' + firedraftAjax.i18n.generating_pdf);
                    $btn.prop('disabled', true);

                    self.showStatus(firedraftAjax.i18n.generating_pdf, 'loading');

                    try {
                        self.generatePDF();
                        self.showStatus(firedraftAjax.i18n.pdf_exported, 'success');
                    } catch (error) {
                        console.error('PDF generation error:', error);
                        self.showStatus(firedraftAjax.i18n.pdf_error + ' ' + error.message, 'error');
                    } finally {
                        $btn.html(originalText);
                        $btn.prop('disabled', false);
                    }
                });
            },

            generatePDF: function() {
                // Check for jsPDF in various global contexts
                var jsPDFLib = null;
                if (typeof window.jspdf !== 'undefined' && window.jspdf.jsPDF) {
                    jsPDFLib = window.jspdf.jsPDF;
                } else if (typeof jsPDF !== 'undefined') {
                    jsPDFLib = jsPDF;
                } else if (typeof window.jsPDF !== 'undefined') {
                    jsPDFLib = window.jsPDF;
                }

                if (!jsPDFLib) {
                    throw new Error('jsPDF library not loaded properly. Please refresh the page and try again.');
                }

                var doc = new jsPDFLib('p', 'mm', 'a4');
                var pageWidth = doc.internal.pageSize.getWidth();
                var pageHeight = doc.internal.pageSize.getHeight();
                var margin = 20;
                var currentY = margin;

                // Document metadata
                var docTitle = $('#document-title').val() || firedraftAjax.i18n.firedraft_report;
                var docAuthor = $('#document-author').val() || 'Firedraft';
                
                // Styling options
                var headingSize = parseInt($('#subheading-size').val()) || 16;
                var headingFont = $('#subheading-font').val() || 'helvetica';
                var headingColor = $('#subheading-color').wpColorPicker('color') || '#ff6b35';
                var bodySize = parseInt($('#body-size').val()) || 12;
                var bodyFont = $('#body-font').val() || 'helvetica';
                var lineSpacing = parseFloat($('#line-spacing').val()) || 1.5;

                doc.setProperties({
                    title: docTitle,
                    author: docAuthor,
                    creator: 'Firedraft Reports'
                });

                // Cover Page
                if ($('#include-cover').is(':checked') && this.canvas && this.canvas.getObjects().length > 0) {
                    var coverData = this.canvas.toDataURL({ format: 'png', quality: 1.0 });
                    doc.addImage(coverData, 'PNG', 0, 0, pageWidth, pageHeight);
                    doc.addPage();
                    currentY = margin;
                }

                // Table of Contents
                if ($('#include-toc').is(':checked')) {
                    doc.setFont(headingFont, 'bold');
                    doc.setFontSize(18);
                    doc.setTextColor(headingColor);
                    doc.text(firedraftAjax.i18n.table_of_contents, margin, currentY);
                    currentY += 20;

                    // Collect headings for TOC
                    var headings = $('.handover-subheading');
                    doc.setFont(bodyFont, 'normal');
                    doc.setFontSize(bodySize);
                    doc.setTextColor('#000000');
                    
                    headings.each(function(index, heading) {
                        if (currentY > pageHeight - 40) {
                            doc.addPage();
                            currentY = margin;
                        }
                        var headingText = $(heading).text();
                        if (headingText.indexOf('Required Data to Improve') === -1 && 
                            headingText.indexOf('Developer Note') === -1) {
                            doc.text((index + 1) + '. ' + headingText, margin, currentY);
                            currentY += bodySize * lineSpacing;
                        }
                    });

                    doc.addPage();
                    currentY = margin;
                }

                // Report Content
                var sections = $('.firedraft-editor-section');
                sections.each(function() {
                    var $section = $(this);
                    var $heading = $section.find('.handover-subheading');
                    var $developerNote = $section.find('.firedraft-developer-note');
                    
                    // Skip developer notes in PDF export
                    if ($developerNote.length > 0) {
                        return;
                    }

                    if ($heading.length > 0) {
                        var headingText = $heading.text();
                        
                        // Check if we need a new page
                        if (currentY > pageHeight - 60) {
                            doc.addPage();
                            currentY = margin;
                        }

                        // Add heading
                        doc.setFont(headingFont, 'bold');
                        doc.setFontSize(headingSize);
                        doc.setTextColor(headingColor);
                        doc.text(headingText, margin, currentY);
                        currentY += headingSize * 1.5;

                        // Add content
                        var $editor = $section.find('.wp-editor-area');
                        if ($editor.length > 0) {
                            var content = '';
                            var editorId = $editor.attr('id');
                            
                            // Try to get content from TinyMCE editor
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                                content = tinyMCE.get(editorId).getContent({format: 'text'});
                            } else {
                                content = $editor.val();
                            }

                            // Strip HTML and clean up content
                            content = content.replace(/<[^>]*>/g, ' ')
                                           .replace(/\s+/g, ' ')
                                           .trim();

                            if (content) {
                                doc.setFont(bodyFont, 'normal');
                                doc.setFontSize(bodySize);
                                doc.setTextColor('#000000');

                                // Split content into lines that fit the page
                                var maxWidth = pageWidth - (2 * margin);
                                var lines = doc.splitTextToSize(content, maxWidth);
                                
                                lines.forEach(function(line) {
                                    if (currentY > pageHeight - 30) {
                                        doc.addPage();
                                        currentY = margin;
                                    }
                                    doc.text(line, margin, currentY);
                                    currentY += bodySize * lineSpacing;
                                });
                            }
                        }

                        currentY += 10; // Add space between sections
                    }
                });

                // Page numbers
                if ($('#include-page-numbers').is(':checked')) {
                    var pageCount = doc.internal.getNumberOfPages();
                    for (var i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.setFont(bodyFont, 'normal');
                        doc.setFontSize(10);
                        doc.setTextColor('#666666');
                        doc.text('Page ' + i + ' of ' + pageCount, pageWidth - 40, pageHeight - 10);
                    }
                }

                // Save the PDF
                var fileName = (docTitle.toLowerCase().replace(/\s+/g, '-') || 'firedraft-report') + '.pdf';
                doc.save(fileName);
            },

            saveCover: function() {
                if (this.canvas) {
                    localStorage.setItem('firedraftCover', JSON.stringify(this.canvas));
                }
            },

            showStatus: function(message, type) {
                type = type || 'loading';
                var icon = 'update';
                if (type === 'success') icon = 'yes';
                if (type === 'error') icon = 'no';
                
                var statusHtml = '<div class="firedraft-status ' + type + '">' +
                    '<span class="dashicons dashicons-' + icon + '"></span>' + message +
                '</div>';
                
                $('#status-area').html(statusHtml).show();
                
                if (type === 'success' || type === 'error') {
                    setTimeout(function() {
                        $('#status-area').fadeOut();
                    }, 5000);
                }
            }
        };

        // Initialize the admin interface
        FiredraftAdmin.init();
    });

})(jQuery);