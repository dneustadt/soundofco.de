<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="stylesheet" type="text/css" href="web/css/style.min.css" media="all">
        <title>soundofcode</title>
        <link rel="apple-touch-icon" sizes="76x76" href="apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
        <link rel="manifest" href="site.webmanifest">
        <link rel="mask-icon" href="safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">
    </head>
    <body>
        <div id="preloader" class="loading-indicator"></div>
        <div id="app" class="start" style="display: none;">
            <form class="row row-wrap"
                  method="POST"
                  action="notes.php"
                  @submit.prevent="getRepo">
                <div class="column">
                    <a href="/">
                        <h1>
                            <span class="color--primary">sound</span><span class="color--primary-light">of</span><span class="color--primary">code</span>
                        </h1>
                    </a>
                    <p class="intro">create sound from code</p>
                    <p class="intro">
                        Enter a <a href="https://github.com" target="_blank"><i class="icon-github"></i> GitHub</a> owner / repository to start
                    </p>
                </div>
                <div class="column column-40 owner">
                    <input type="text" name="owner" placeholder="Owner" required>
                </div>
                <div class="column column-40 repo">
                    <input type="text" name="repo" placeholder="Repository" required>
                </div>
                <div class="column column-20">
                    <button type="submit" class="float-right"><i class="icon-enter"></i></button>
                </div>
            </form>
            <div class="row">
                 <div class="column"
                      id="controls"
                      v-if="code && notes">
                     <div>
                         <button v-on:click="back"><i class="icon-eject"></i></button>
                         <button v-on:click="play"
                                 v-if="!isPlaying"><i class="icon-play3"></i></button>
                         <button v-on:click="stop"
                                 v-if="isPlaying"><i class="icon-stop2"></i></button>
                         <div class="select">
                             <label for="wavetype">
                                 Waveform
                             </label>
                             <select id="wavetype"
                                     v-model="wavetype">
                                 <option value="sine">Sine</option>
                                 <option value="square">Square</option>
                                 <option value="sawtooth">Sawtooth</option>
                                 <option value="triangle">Triangle</option>
                             </select>
                         </div>
                     </div>
                     <div>
                         <div class="slider">
                             <label for="interval">
                                 <i class="icon-stopwatch"></i> Interval
                             </label>
                             <input type="range"
                                    id="interval"
                                    min="0"
                                    max="1000"
                                    step="1"
                                    :title="interval + 'ms'"
                                    v-model="interval">
                         </div>
                         <div class="slider">
                             <label for="note-length">
                                 <i class="icon-music"></i> Note Length
                             </label>
                             <input type="range"
                                    id="note-length"
                                    min="50"
                                    max="1000"
                                    step="1"
                                    id="note-length"
                                    :title="noteLength + 'ms'"
                                    v-model="noteLength">
                         </div>
                         <div class="slider">
                             <label for="volume">
                                 <i class="icon-volume-high" v-if="volume > 0"></i><i class="icon-volume-mute2" v-else></i> Volume
                             </label>
                             <input type="range"
                                    id="volume"
                                    min="0"
                                    max="1"
                                    step="0.1"
                                    id="volume"
                                    v-model="volume">
                         </div>
                     </div>
                 </div>
                <div class="row browserbar"
                     v-else-if="tree">
                    <div class="column">
                        <p><strong>Browsing {{ owner }}/{{ repo }}</strong></p>
                        <p>Choose a file to listen to</p>
                    </div>
                </div>
            </div>
            <div class="row"
                 id="codeview"
                 v-if="code && notes && !isLoading">
                <div class="column column-50">
                    <pre id="codescroll"><a
                        href="#"
                        v-for="(line, index) in code"
                        v-on:click.stop.prevent="lineClick($event, index)"
                        :data-current-line="index === currentNote - 1">{{ line }}<br></a></pre>
                </div>
                <div class="column column-50"
                     id="waveform">
                    <canvas id="notegraph" width="1000" height="1000" v-on:click="spectrumClick"></canvas>
                    <div id="waveform-needle"></div>
                </div>
            </div>
            <div class="row"
                 id="treeview"
                 v-else-if="tree && !isLoading">
                <div class="column"
                     id="tree">
                    <div v-for="path in tree">
                        <span v-on:click="getNotes(path)">
                            <i class="icon-volume-high"></i> {{ path }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="row"
                 v-else-if="isLoading">
                <div class="column">
                    <div class="loading-indicator"></div>
                </div>
            </div>
        </div>
        <div class="simple-notification-container" id="notificationContainer"></div>
        <a href="https://github.com/dneustadt/soundofco.de" class="github-corner" target="_blank" aria-label="Visit on Github">
            <svg width="80" height="80" viewBox="0 0 250 250" aria-hidden="true">
                <path d="M0,0 L115,115 L130,115 L142,142 L250,250 L250,0 Z"></path>
                <path d="M128.3,109.0 C113.8,99.7 119.0,89.6 119.0,89.6 C122.0,82.7 120.5,78.6 120.5,78.6 C119.2,72.0 123.4,76.3 123.4,76.3 C127.3,80.9 125.5,87.3 125.5,87.3 C122.9,97.6 130.6,101.9 134.4,103.2" fill="currentColor" style="transform-origin: 130px 106px;" class="octo-arm"></path>
                <path d="M115.0,115.0 C114.9,115.1 118.7,116.5 119.8,115.4 L133.7,101.6 C136.9,99.2 139.9,98.4 142.2,98.6 C133.8,88.0 127.5,74.4 143.8,58.0 C148.5,53.4 154.0,51.2 159.7,51.0 C160.3,49.4 163.2,43.6 171.4,40.1 C171.4,40.1 176.1,42.5 178.8,56.2 C183.1,58.6 187.2,61.8 190.9,65.4 C194.5,69.0 197.7,73.2 200.1,77.6 C213.8,80.2 216.3,84.9 216.3,84.9 C212.7,93.1 206.9,96.0 205.4,96.6 C205.1,102.4 203.0,107.8 198.3,112.5 C181.9,128.9 168.3,122.5 157.7,114.1 C157.9,116.9 156.7,120.9 152.7,124.9 L141.0,136.5 C139.8,137.7 141.6,141.9 141.8,141.8 Z" fill="currentColor" class="octo-body"></path>
            </svg>
        </a>
        <script type="text/javascript">
            window.presetOptions = {};
            <?php if (!empty($_GET['o'])): ?>window.presetOptions.owner = <?= json_encode((string) $_GET['o']); ?>;<?php endif; ?>
            <?php if (!empty($_GET['r'])): ?>window.presetOptions.repo = <?= json_encode((string) $_GET['r']); ?>;<?php endif; ?>
            <?php if (!empty($_GET['b'])): ?>window.presetOptions.branch = <?= json_encode((string) $_GET['b']); ?>;<?php endif; ?>
            <?php if (!empty($_GET['p'])): ?>window.presetOptions.path = <?= json_encode((string) $_GET['p']); ?>;<?php endif; ?>
            <?php if (!empty($_GET['i']) && (int) $_GET['i'] >= 0 && (int) $_GET['i'] <= 1000): ?>window.presetOptions.interval = <?= (int) $_GET['i']; ?>;<?php endif; ?>
            <?php if (!empty($_GET['n']) && (int) $_GET['n'] >= 50 && (int) $_GET['n'] <= 1000): ?>window.presetOptions.noteLength = <?= (int) $_GET['n']; ?>;<?php endif; ?>
            <?php if (!empty($_GET['w']) && in_array($_GET['w'], ['sine', 'sawtooth', 'triangle'])): ?>window.presetOptions.wavetype = '<?= $_GET['w']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['l'])): ?>window.presetOptions.currentNote = <?= (int) $_GET['l'] + 1; ?>;<?php endif; ?>
        </script>
        <script type="text/javascript" src="web/js/scripts.min.js"></script>
    </body>
</html>