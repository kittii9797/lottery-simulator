<template>
    <div class="lotto-app">
        <div class="statistics datas">
            <div class="stats-data">
                <div class="d-flex">
                    <p>Years spent:</p>
                    <p><b>{{ years }}</b></p>
                </div>
                <div class="d-flex">
                    <p>Number of tickets:</p>
                    <p><b>{{ totalTickets }}</b></p>
                </div>
                <div class="d-flex">
                    <p>Cost of tickets:</p>
                    <p><b>{{ totalCost }} HUF</b></p>
                </div>
            </div>
            <div class="statistics-match">
                <div class="match-counts">
                    <div v-for="(count, matches) in filteredMatchCounts" :key="matches" class="match-box">
                        <div class="text-center">
                            <p>{{ matches }} matches</p>
                            <p class="matches-count-number mt-3">{{ count }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-input">
            <div class="number-picker">
                <div v-for="number in availableNumbers" :key="number" class="number"
                    :class="{ selected: isSelected(number) }" @click="toggleNumber(number)"
                    :disabled="allButtonsDisabled">
                    {{ number }}
                </div>
            </div>
            <p v-if="selectedNumbers.length < 5" class="warning">Choose 5 numbers!</p>
            <div class="buttons">
                <button class="btn btn-success" @click="submitTicket"
                    :disabled="selectedNumbers.length !== 5 || allButtonsDisabled">Submit
                    ticket</button>
                <button class="btn btn-secondary" @click="generateRandomNumbers" :disabled="allButtonsDisabled">Random
                    numbers</button>
            </div>
        </div>

        <div class="your-numbers">
            <p><b>Your tickets:</b></p>
            <div v-for="(ticket, index) in selectedTickets" :key="index" class="ticket"
                :class="{ winning: winningTickets === ticket }">
                <span v-for="number in ticket" :key="number" class="number">{{ number }}</span>
            </div>
        </div>

        <div class="drawn-numbers">
            <p><b>Last Winning numbers:</b></p>
            <div>
                <span v-for="(number, index) in displayedNumbers" :key="index" class="number">{{ number }}</span>
            </div>
        </div>

        <div class="settings">
            <label for="speed"><b>Speed (sec):</b></label>
            <input type="range" id="speed" v-model="speed" min="1" max="1000" @input="saveSpeed"
                :disabled="allButtonsDisabled">
        </div>

        <div class="buttons-result">
            <button v-if="showGetStarted" class="btn btn-primary" @click="resetGame">Get started</button>
            <button class="btn btn-primary" @click="startDrawing"
                :disabled="allButtonsDisabled || !selectedTickets.length">Start draw</button>
        </div>
    </div>
</template>


<script>
export default {
    data() {
        return {
            drawnNumbers: [],
            selectedNumbers: [],
            selectedTickets: [],
            displayedNumbers: [],
            speed: 1000,
            years: 0,
            matchCounts: {
                2: 0,
                3: 0,
                4: 0,
                5: 0
            },
            totalCost: 0,
            totalTickets: 0,
            availableNumbers: Array.from({ length: 90 }, (_, i) => i + 1),
            isDrawing: false,
            allButtonsDisabled: false,
            showGetStarted: false,
            winningTickets: []
        };
    },
    computed: {
        filteredMatchCounts() {
            return Object.fromEntries(
                Object.entries(this.matchCounts).filter(([matches, count]) => matches > 1)
            );
        },
    },
    methods: {
        async fetchAllDraws() {
            try {
                const response = await fetch('http://127.0.0.1:8000/api/draws');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const draws = await response.json();
                this.allDraws = draws;
            } catch (error) {
                console.error('Failed to fetch all draws:', error);
            }
        },
        async checkForWinners() {
            await this.fetchAllDraws();

            let foundFiveMatches = false;

            this.selectedTickets.forEach(ticket => {
                const ticketNumbers = ticket;

                this.allDraws.forEach(draw => {
                    const drawnNumbers = draw.numbers;

                    const matches = ticketNumbers.filter(number => drawnNumbers.includes(number)).length;

                    if (matches === 5) {
                        console.log(`Ticket ${ticket} has 5 matches with draw ${draw.id}`);
                        this.winningTickets = ticket || [];
                        foundFiveMatches = true;
                    }
                });
            });

            if (foundFiveMatches) {
                alert('Congratulations! A ticket with 5 matches has been found.');
                localStorage.setItem('displayedNumbers', JSON.stringify(this.winningTickets));
            }
        },
        startDrawing() {
            this.isDrawing = true;
            this.allButtonsDisabled = true;

            fetch('http://127.0.0.1:8000/api/results', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(result => {
                    this.drawnNumbers = result.numbers || [];
                })
                .catch(error => {
                    console.error("Failed to fetch results:", error);
                    this.isDrawing = false;
                    this.allButtonsDisabled = false;
                });

            this.fetchLastResult();
            this.updateStatistics();
            this.saveState();
        },
        fetchLastResult() {
            fetch('http://127.0.0.1:8000/api/last-result')
                .then(response => response.json())
                .then(result => {
                    // Verify the result structure
                    if (result && Array.isArray(result.numbers)) {
                        this.displayedNumbers = result.numbers;
                        console.log('Numbers fetched:', this.displayedNumbers);
                    } else {
                        console.error('Unexpected response format:', result);
                        this.displayedNumbers = [];
                    }

                    // Convert array to object format and store in localStorage
                    const formattedNumbers = this.displayedNumbers.reduce((acc, number, index) => {
                        acc[index] = number;
                        return acc;
                    }, {});
                    localStorage.setItem('displayedNumbers', JSON.stringify(formattedNumbers));

                    this.showGetStarted = true;
                })
                .catch(error => {
                    console.error("Failed to fetch the last result:", error);
                    this.displayedNumbers = [];
                });
        },
        generateRandomNumbers() {
            const numbers = Array.from({ length: 5 }, () => Math.floor(Math.random() * 90) + 1);
            this.selectedNumbers = numbers;
        },
        toggleNumber(number) {
            if (this.allButtonsDisabled) return;

            const index = this.selectedNumbers.indexOf(number);
            if (index > -1) {
                this.selectedNumbers.splice(index, 1);
            } else if (this.selectedNumbers.length < 5) {
                this.selectedNumbers.push(number);
            }
        },
        isSelected(number) {
            return this.selectedNumbers.includes(number);
        },
        submitTicket() {
            if (this.selectedNumbers.length === 5 && !this.allButtonsDisabled) {

                const isDuplicate = this.selectedTickets.some(
                    ticket => JSON.stringify(ticket.sort()) === JSON.stringify([...this.selectedNumbers].sort())
                );

                if (isDuplicate) {
                    alert("This ticket combination has already been submitted.");
                    return;
                }

                this.selectedTickets.push([...this.selectedNumbers]);
                localStorage.setItem('selectedTickets', JSON.stringify(this.selectedTickets));

                fetch('http://127.0.0.1:8000/api/tickets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ numbers: this.selectedNumbers })
                }).then(() => {
                    this.selectedNumbers = [];
                    this.updateStatistics();
                });
            }
        },
        updateStatistics() {
            fetch('http://127.0.0.1:8000/api/statistics')
                .then(response => response.json())
                .then(stats => {
                    this.years = Math.round(stats.totalResults / 52);

                    if (stats.totalResults !== 0) {
                        this.totalTickets = stats.totalTickets * stats.totalResults;
                        this.totalCost = stats.totalCost * stats.totalResults;
                    }
                    else {
                        this.totalTickets = stats.totalTickets;
                        this.totalCost = stats.totalCost;
                    }
                    this.matchCounts = {
                        ...this.matchCounts, ...stats.results.reduce((acc, result) => {
                            acc[result.matches] = result.count;
                            return acc;
                        }, {})
                    };

                    this.checkForWinners()

                });
        },
        reloadPage() {
            location.reload();
        },
        resetGame() {
            fetch('http://127.0.0.1:8000/api/reset', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(() => {
                    this.selectedTickets = [];
                    this.updateStatistics();
                });
            this.clearState();
            this.reloadPage();
        },
        saveState() {
            localStorage.setItem('isDrawing', this.isDrawing);
            localStorage.setItem('showGetStarted', this.showGetStarted);
            localStorage.setItem('selectedTickets', JSON.stringify(this.selectedTickets));
            localStorage.setItem('speed', this.speed);
        },
        loadState() {
            const savedIsDrawing = localStorage.getItem('isDrawing') === 'true';
            const savedShowGetStarted = localStorage.getItem('showGetStarted') === 'false';
            const savedSelectedTickets = JSON.parse(localStorage.getItem('selectedTickets')) || [];
            const savedDisplayedNumbers = JSON.parse(localStorage.getItem('displayedNumbers')) || [];
            const savedSpeed = parseInt(localStorage.getItem('speed'), 10) || 1000;

            this.isDrawing = savedIsDrawing;
            this.showGetStarted = savedShowGetStarted;
            this.selectedTickets = savedSelectedTickets;
            this.displayedNumbers = savedDisplayedNumbers;
            this.speed = savedSpeed;
            this.allButtonsDisabled = savedIsDrawing;
        },
        clearState() {
            localStorage.removeItem('isDrawing');
            localStorage.removeItem('showGetStarted');
            localStorage.removeItem('selectedTickets');
            localStorage.removeItem('displayedNumbers');
            localStorage.removeItem('speed');

            this.showGetStarted = false;
            this.allButtonsDisabled = false;
            this.isDrawing = false;
        },
        saveSpeed() {
            this.saveState(); // Save speed to localStorage on change
        }
    },
    mounted() {
        this.loadState(); // Load saved state on mount
        this.updateStatistics();
    }
};
</script>

<style lang="scss">
@import '../../sass/lotto.scss';
</style>
