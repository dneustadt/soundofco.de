var optionKeys = {
        o: 'owner',
        r: 'repo',
        b: 'branch',
        p: 'path',
        i: 'interval',
        n: 'noteLength',
        w: 'wavetype'
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
            if (this.currentNote === undefined) {
                return;
            }

            var codeScroll = document.getElementById('codescroll'),
                waveformNeedle = document.getElementById('waveform-needle'),
                currentLine = codeScroll.children[this.currentNote];

            if (currentLine === undefined) {
                return;
            }

            if ((currentLine.offsetTop - codeScroll.offsetTop) > (codeScroll.offsetHeight / 2) + codeScroll.scrollTop) {
                var scrollDiff = currentLine.offsetTop - (codeScroll.offsetHeight / 2 + codeScroll.scrollTop),
                    scrollAmount = 0,
                    slideTimer = setInterval(function(){
                        codeScroll.scrollTop += 10;
                        scrollAmount += 10;
                        if(scrollAmount >= scrollDiff){
                            window.clearInterval(slideTimer);
                        }
                    }, 10);
            }

            waveformNeedle.style.left = (this.currentNote / this.notes.length * 100).toFixed(1) + '%';
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
                        });
                    } else {
                        this.isLoading = false;
                        simpleNotify.notify({message: response.body.message, level: 'warning', notificationTime: 3000});
                    }
                });
        },
        back: function () {
            if (this.isPlaying) {
                this.currentNote = this.notes.length;
                this.isPlaying = false;
            }

            if (this.tree === undefined && this.owner && this.repo) {
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
        },
        play: function () {
            document.getElementById('codescroll').scrollTop = 0;
            document.getElementById('waveform-needle').style.left = '';
            this.currentNote = 0;
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
                    if (document.getElementById('waveform-needle')) {
                        document.getElementById('waveform-needle').style.left = '';
                    }
                }
            }, self.noteLength)
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
        }
    }
});