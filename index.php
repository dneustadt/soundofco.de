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
                    <pre id="codescroll"><span
                        v-for="(line, index) in code"
                        :data-current-line="index === currentNote - 1 && isPlaying">{{ line }}<br></span></pre>
                </div>
                <div class="column column-50"
                     id="waveform">
                    <canvas id="notegraph" width="1000" height="1000"></canvas>
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
        <script type="text/javascript">
            window.presetOptions = {};
            <?php if (!empty($_GET['o'])): ?>window.presetOptions.owner = '<?= $_GET['o']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['r'])): ?>window.presetOptions.repo = '<?= $_GET['r']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['b'])): ?>window.presetOptions.branch = '<?= $_GET['b']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['p'])): ?>window.presetOptions.path = '<?= $_GET['p']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['i'])): ?>window.presetOptions.interval = '<?= $_GET['i']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['n'])): ?>window.presetOptions.noteLength = '<?= $_GET['n']; ?>';<?php endif; ?>
            <?php if (!empty($_GET['w'])): ?>window.presetOptions.wavetype = '<?= $_GET['w']; ?>';<?php endif; ?>
        </script>
        <script type="text/javascript" src="web/js/scripts.min.js"></script>
    </body>
</html>