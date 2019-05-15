var optionKeys = {
        o: 'owner',
        r: 'repo',
        b: 'branch',
        p: 'path',
        i: 'interval',
        n: 'noteLength',
        w: 'wavetype',
        l: 'currentNote'
    },
    defaultOptions = {
        owner: undefined,
        repo: undefined,
        branch: undefined,
        tree: undefined,
        path: undefined,
        notes: undefined,
        code: undefined,
        audioContext: window.AudioContext ? new AudioContext() : new window.webkitAudioContext,
        oscillator: undefined,
        gain: undefined,
        currentNote: 0,
        interval: 20,
        noteLength: 200,
        volume: 0.2,
        wavetype: 'square',
        isPlaying: false,
        isLoading: false
    },
    app = new Vue({
    el: '#app',
    data: Object.assign(Object.assign({}, defaultOptions), window.presetOptions),
    watch: {
        currentNote: function () {
            this.moveCursor();
        },
        owner: function () {
            this.updateQueryString('o', this.owner);
        },
        repo: function () {
            this.updateQueryString('r', this.repo);
        },
        branch: function () {
            this.updateQueryString('b', this.branch);
        },
        path: function () {
            this.updateQueryString('p', this.path);
        },
        interval: function () {
            this.updateQueryString('i', this.interval);
        },
        noteLength: function () {
            this.updateQueryString('n', this.noteLength);
        },
        wavetype: function () {
            this.updateQueryString('w', this.wavetype);
        }
    },
    mounted: function() {
        document.getElementById('preloader').style.display = 'none';
        this.$el.style.display = '';

        if (this.owner && this.repo && this.branch && this.path) {
            this.getNotes(this.path);
        } else if (this.owner && this.repo) {
            var formData = new FormData();
            formData.append('owner', this.owner);
            formData.append('repo', this.repo);

            this.getRepo(undefined, formData, 'notes.php');
        }
    },
    methods: {
        getRepo: function (event, data, dataSrc) {
            this.$el.classList.remove('start');

            if (event) {
                event.preventDefault();

                this.back();

                var form = event.target.closest('form'),
                    formData = new FormData(form),
                    src = form.getAttribute('action');

                this.owner = formData.get('owner');
                this.repo = formData.get('repo');
            } else {
                var formData = data,
                    src = dataSrc;
            }

            this.isLoading = true;

            this.$http.post(src, formData)
                .then(function (response) {
                    if (response.body.success === true) {
                        this.notes = undefined;
                        this.code = undefined;
                        this.branch = response.body.branch;
                        this.tree = response.body.tree;
                        this.isLoading = false;
                    } else {
                        this.isLoading = false;
                        this.tree = undefined;
                        simpleNotify.notify({message: response.body.message, level: 'warning', notificationTime: 3000});
                    }
                });
        },
        getNotes: function (path) {
            this.$el.classList.remove('start');

            var formData = new FormData();
            formData.append('owner', this.owner);
            formData.append('repo', this.repo);
            formData.append('branch', this.branch);
            formData.append('path', path);

            this.isLoading = true;

            this.$http.post('notes.php', formData)
                .then(function (response) {
                    if (response.body.success === true) {
                        this.notes = response.body.notes;
                        this.code = response.body.lines;
                        this.path = path;
                        this.isLoading = false;

                        this.$nextTick(function () {
                            var canvas = document.getElementById('notegraph');

                            if(canvas.getContext){
                                var ctx = canvas.getContext("2d");

                                ctx.fillStyle = "rgb(200,200,200)";

                                for (i = 0; i < this.notes.length; i++) {
                                    ctx.fillRect(
                                        i * (1000 / this.notes.length),
                                        500 - (this.notes[i] / 2),
                                        Math.ceil(1000 / this.notes.length) + 0.5,
                                        this.notes[i]
                                    );
                                }
                            }

                            if (!this.currentNote) {
                                return;
                            }

                            this.moveCursor(true);
                        });
                    } else {
                        this.isLoading = false;
                        simpleNotify.notify({message: response.body.message, level: 'warning', notificationTime: 3000});
                    }
                });
        },
        back: function (event) {
            if (this.isPlaying) {
                this.currentNote = this.notes.length;
                this.isPlaying = false;
            } else {
                this.currentNote = 0;
            }

            if (event && this.tree === undefined && this.owner && this.repo) {
                var formData = new FormData();
                formData.append('owner', this.owner);
                formData.append('repo', this.repo);

                this.getRepo(undefined, formData, 'notes.php');
            }

            this.notes = undefined;
            this.code = undefined;
            this.path = undefined;
            this.interval = defaultOptions.interval;
            this.noteLength = defaultOptions.noteLength;
            this.wavetype = defaultOptions.wavetype;
            this.removeQueryString('l');
        },
        play: function () {
            if (this.isPlaying) {
                this.currentNote = this.notes.length;
                this.isPlaying = false;
            }

            this.oscillator = this.audioContext.createOscillator();
            this.gain = this.audioContext.createGain();
            this.gain.gain.value = 0;
            this.oscillator.connect(this.gain);
            this.oscillator.type = this.wavetype;
            this.gain.connect(this.audioContext.destination)
            this.oscillator.start(0);
            this.isPlaying = true;
            this.playTune();
        },
        stop: function () {
            this.currentNote = this.notes.length;
            document.getElementById('waveform-needle').style.left = '';
            this.isPlaying = false;
        },
        noteInterval: function () {
            var self = this;

            self.gain.gain.setTargetAtTime(0, self.audioContext.currentTime, 0.015);
            setTimeout(function () {
                self.gain.gain.setTargetAtTime(self.volume, self.audioContext.currentTime, 0.015);
                self.playTune();
            }, self.interval);
        },
        playTune: function () {
            var self = this;

            setTimeout(function () {
                if (self.notes && self.currentNote < self.notes.length) {
                    self.oscillator.frequency.value = self.notes[self.currentNote];
                    self.oscillator.type = self.wavetype;
                    self.currentNote++;

                    if (self.interval) {
                        self.noteInterval();
                    } else {
                        self.gain.gain.setTargetAtTime(self.volume, self.audioContext.currentTime, 0.015);
                        self.playTune();
                    }
                } else {
                    self.oscillator.stop();
                    self.isPlaying = false;
                    self.currentNote = 0;
                }
            }, self.noteLength)
        },
        lineClick: function (event, lineNum) {
            if (this.isPlaying) {
                event.target.blur();

                return;
            }

            this.currentNote = lineNum + 1;
            this.updateQueryString('l', lineNum);
        },
        spectrumClick: function (event) {
            var spectrum = event.target,
                spectrumWidth = spectrum.offsetWidth,
                spectrumLeft = spectrum.getBoundingClientRect().left,
                cursorX = event.clientX,
                xPercent = ((cursorX - spectrumLeft) / spectrumWidth).toFixed(2) * 100;

            if (!this.notes) {
                return;
            }

            this.currentNote = (this.notes.length / 100 * xPercent).toFixed(0);
            this.updateQueryString('l', this.currentNote);
            this.moveCursorToLine(this.currentNote);
        },
        moveCursor: function (forceScroll) {
            if (this.currentNote === undefined) {
                return;
            }

            var self = this,
                codeScroll = document.getElementById('codescroll'),
                waveformNeedle = document.getElementById('waveform-needle'),
                currentLine = codeScroll.children[self.currentNote],
                scrollAmount = 50,
                getOffset = function () {
                    return currentLine.offsetTop - codeScroll.offsetTop
                },
                getScrollTop = function () {
                    return codeScroll.offsetHeight / 2 + codeScroll.scrollTop;
                },
                isScrollBottom = function () {
                    return codeScroll.scrollTop === (codeScroll.scrollHeight - codeScroll.offsetHeight);
                };

            if (currentLine === undefined) {
                return;
            }

            if (getOffset() >= getScrollTop()) {
                var slideTimer = setInterval(function () {
                    var clearInterval = forceScroll ? false : !self.isPlaying;

                    codeScroll.scrollTop += 10;

                    if (getOffset() < getScrollTop() - scrollAmount || isScrollBottom() || clearInterval) {
                        window.clearInterval(slideTimer);
                    }
                }, 10);
            } else if (getOffset() < getScrollTop() - scrollAmount && self.isPlaying) {
                if (document.getElementById('codescroll')) {
                    document.getElementById('codescroll').scrollTop = 0;
                }
            }

            if (self.notes) {
                waveformNeedle.style.left = (self.currentNote / self.notes.length * 100).toFixed(1) + '%';
            }
        },
        moveCursorToLine: function (line) {
            var codeScroll = document.getElementById('codescroll'),
                currentLine = codeScroll.children[line];

            codeScroll.scrollTop = currentLine.offsetTop - (codeScroll.offsetWidth / 2);
        },
        updateQueryString: function (key, value) {
            var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
                urlQueryString = document.location.search,
                newParam = key + '=' + value,
                params = '?' + newParam;

            if (urlQueryString) {
                var keyRegex = new RegExp('([\?&])' + key + '[^&]*'),
                    optionKey = optionKeys[key];

                if (urlQueryString.match(keyRegex) !== null) {
                    if (value !== undefined && value !== defaultOptions[optionKey]) {
                        params = urlQueryString.replace(keyRegex, "$1" + newParam);
                    } else {
                        params = urlQueryString.replace(keyRegex, '');
                    }
                } else if (value !== undefined && value !== defaultOptions[optionKey]) {
                    params = urlQueryString + '&' + newParam;
                }
            }
            window.history.replaceState({}, '', baseUrl + params);
        },
        removeQueryString: function (param) {
            var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
                urlQueryString = document.location.search,
                prefix = encodeURIComponent(param) + '=',
                pars = urlQueryString.split(/[&;]/g);

            for (var i = pars.length; i-- > 0;) {
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }

            window.history.replaceState({}, '', baseUrl + (pars.length > 0 ? pars.join('&') : ''));
        }
    }
});