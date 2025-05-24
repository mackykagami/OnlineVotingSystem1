<?php
require_once 'config.php';

/**
 * Election Management Class
 * Handles election creation, management, and voting processes
 */
class Election {
    private $conn;
    private $table_name = "elections";

    public $id;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $status;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new election
     * @return bool|string True on success, error message on failure
     */
    public function create() {
        // Validate required fields
        if (empty($this->title) || empty($this->start_date) || empty($this->end_date)) {
            return "Title, start date, and end date are required.";
        }

        // Validate dates
        $start_timestamp = strtotime($this->start_date);
        $end_timestamp = strtotime($this->end_date);
        $current_timestamp = time();

        if ($start_timestamp < $current_timestamp) {
            return "Start date cannot be in the past.";
        }

        if ($end_timestamp <= $start_timestamp) {
            return "End date must be after start date.";
        }

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (title, description, start_date, end_date, created_by) 
                     VALUES (:title, :description, :start_date, :end_date, :created_by)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $this->title);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':start_date', $this->start_date);
            $stmt->bindParam(':end_date', $this->end_date);
            $stmt->bindParam(':created_by', $this->created_by);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                Utils::logActivity($this->created_by, 'ELECTION_CREATED', 'Election created: ' . $this->title);
                return true;
            }
            
            return "Election creation failed.";
        } catch (PDOException $e) {
            error_log("Election creation error: " . $e->getMessage());
            return "Election creation failed.";
        }
    }

    /**
     * Update election
     * @return bool|string
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET title = :title, description = :description, 
                         start_date = :start_date, end_date = :end_date 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->bindParam(':title', $this->title);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':start_date', $this->start_date);
            $stmt->bindParam(':end_date', $this->end_date);

            if ($stmt->execute()) {
                Utils::logActivity($_SESSION['user_id'], 'ELECTION_UPDATED', 'Election updated: ' . $this->title);
                return true;
            }
            
            return "Election update failed.";
        } catch (PDOException $e) {
            error_log("Election update error: " . $e->getMessage());
            return "Election update failed.";
        }
    }

    /**
     * Delete election
     * @param int $election_id
     * @return bool
     */
    public function delete($election_id) {
        try {
            // Check if election has votes
            $vote_check = "SELECT COUNT(*) as vote_count FROM votes WHERE election_id = :election_id";
            $vote_stmt = $this->conn->prepare($vote_check);
            $vote_stmt->bindParam(':election_id', $election_id);
            $vote_stmt->execute();
            $vote_result = $vote_stmt->fetch(PDO::FETCH_ASSOC);

            if ($vote_result['vote_count'] > 0) {
                return "Cannot delete election with existing votes.";
            }

            $query = "DELETE FROM " . $this->table_name . " WHERE id = :election_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':election_id', $election_id);

            if ($stmt->execute()) {
                Utils::logActivity($_SESSION['user_id'], 'ELECTION_DELETED', 'Election ID ' . $election_id . ' deleted');
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Election deletion error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get election by ID
     * @param int $election_id
     * @return array|false
     */
    public function getById($election_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :election_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':election_id', $election_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get election error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all elections
     * @param string $status Optional status filter
     * @return array
     */
    public function getAll($status = null) {
        try {
            $query = "SELECT e.*, u.full_name as created_by_name 
                     FROM " . $this->table_name . " e 
                     LEFT JOIN users u ON e.created_by = u.id";
            
            if ($status) {
                $query .= " WHERE e.status = :status";
            }
            
            $query .= " ORDER BY e.created_at DESC";

            $stmt = $this->conn->prepare($query);
            
            if ($status) {
                $stmt->bindParam(':status', $status);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all elections error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active elections
     * @return array
     */
    public function getActiveElections() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE start_date <= NOW() AND end_date >= NOW() 
                     ORDER BY start_date ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get active elections error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update election status based on dates
     */
    public function updateElectionStatuses() {
        try {
            // Update to active
            $active_query = "UPDATE " . $this->table_name . " 
                            SET status = 'active' 
                            WHERE start_date <= NOW() AND end_date >= NOW() AND status = 'upcoming'";
            $this->conn->exec($active_query);

            // Update to completed
            $completed_query = "UPDATE " . $this->table_name . " 
                               SET status = 'completed' 
                               WHERE end_date < NOW() AND status IN ('upcoming', 'active')";
            $this->conn->exec($completed_query);

            return true;
        } catch (PDOException $e) {
            error_log("Update election statuses error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get election candidates
     * @param int $election_id
     * @return array
     */
    public function getCandidates($election_id) {
        try {
            $query = "SELECT * FROM candidates WHERE election_id = :election_id ORDER BY position, name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':election_id', $election_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get candidates error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has voted in election
     * @param int $user_id
     * @param int $election_id
     * @return bool
     */
    public function hasUserVoted($user_id, $election_id) {
        try {
            $query = "SELECT id FROM votes WHERE user_id = :user_id AND election_id = :election_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':election_id', $election_id);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Check user voted error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cast vote
     * @param int $user_id
     * @param int $candidate_id
     * @return bool|string
     */
    public function castVote($user_id, $candidate_id) {
        try {
            // Get candidate and election info
            $candidate_query = "SELECT c.*, e.id as election_id, e.status, e.start_date, e.end_date 
                               FROM candidates c 
                               JOIN elections e ON c.election_id = e.id 
                               WHERE c.id = :candidate_id";
            
            $candidate_stmt = $this->conn->prepare($candidate_query);
            $candidate_stmt->bindParam(':candidate_id', $candidate_id);
            $candidate_stmt->execute();

            if ($candidate_stmt->rowCount() == 0) {
                return "Invalid candidate selected.";
            }

            $candidate_data = $candidate_stmt->fetch(PDO::FETCH_ASSOC);
            $election_id = $candidate_data['election_id'];

            // Check if election is active
            $current_time = date('Y-m-d H:i:s');
            if ($current_time < $candidate_data['start_date']) {
                return "Voting has not started yet.";
            }
            
            if ($current_time > $candidate_data['end_date']) {
                return "Voting has ended.";
            }

            // Check if user already voted
            if ($this->hasUserVoted($user_id, $election_id)) {
                return "You have already voted in this election.";
            }

            // Generate vote hash for security
            $vote_hash = Utils::generateHash($user_id . $candidate_id . time());

            // Insert vote
            $vote_query = "INSERT INTO votes (user_id, election_id, candidate_id, vote_hash, ip_address, user_agent) 
                          VALUES (:user_id, :election_id, :candidate_id, :vote_hash, :ip_address, :user_agent)";

            $vote_stmt = $this->conn->prepare($vote_query);
            $vote_stmt->bindParam(':user_id', $user_id);
            $vote_stmt->bindParam(':election_id', $election_id);
            $vote_stmt->bindParam(':candidate_id', $candidate_id);
            $vote_stmt->bindParam(':vote_hash', $vote_hash);
            $vote_stmt->bindParam(':ip_address', Utils::getClientIP());
            $vote_stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');

            if ($vote_stmt->execute()) {
                Utils::logActivity($user_id, 'VOTE_CAST', 'Vote cast for candidate ID ' . $candidate_id . ' in election ID ' . $election_id);
                return true;
            }
            
            return "Vote casting failed. Please try again.";
        } catch (PDOException $e) {
            error_log("Cast vote error: " . $e->getMessage());
            return "Vote casting failed. Please try again.";
        }
    }

    /**
     * Get election results
     * @param int $election_id
     * @return array
     */
    public function getResults($election_id) {
        try {
            $query = "SELECT c.id, c.name, c.party, c.photo,
                            COUNT(v.id) as vote_count,
                            ROUND((COUNT(v.id) * 100.0 / (
                                SELECT COUNT(*) FROM votes WHERE election_id = :election_id2
                            )), 2) as vote_percentage
                     FROM candidates c
                     LEFT JOIN votes v ON c.id = v.candidate_id
                     WHERE c.election_id = :election_id1
                     GROUP BY c.id
                     ORDER BY vote_count DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':election_id1', $election_id);
            $stmt->bindParam(':election_id2', $election_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get results error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total votes for election
     * @param int $election_id
     * @return int
     */
    public function getTotalVotes($election_id) {
        try {
            $query = "SELECT COUNT(*) as total_votes FROM votes WHERE election_id = :election_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':election_id', $election_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_votes'];
        } catch (PDOException $e) {
            error_log("Get total votes error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get election statistics
     * @param int $election_id
     * @return array
     */
    public function getStatistics($election_id) {
        try {
            $stats = [];
            
            // Total registered voters
            $total_users_query = "SELECT COUNT(*) as total FROM users WHERE is_verified = 1";
            $total_stmt = $this->conn->prepare($total_users_query);
            $total_stmt->execute();
            $stats['total_registered_voters'] = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Total votes cast
            $stats['total_votes_cast'] = $this->getTotalVotes($election_id);

            // Voter turnout percentage
            if ($stats['total_registered_voters'] > 0) {
                $stats['voter_turnout'] = round(($stats['total_votes_cast'] / $stats['total_registered_voters']) * 100, 2);
            } else {
                $stats['voter_turnout'] = 0;
            }

            // Total candidates
            $candidates_query = "SELECT COUNT(*) as total FROM candidates WHERE election_id = :election_id";
            $candidates_stmt = $this->conn->prepare($candidates_query);
            $candidates_stmt->bindParam(':election_id', $election_id);
            $candidates_stmt->execute();
            $stats['total_candidates'] = $candidates_stmt->fetch(PDO::FETCH_ASSOC)['total'];

            return $stats;
        } catch (PDOException $e) {
            error_log("Get statistics error: " . $e->getMessage());
            return [];
        }
    }
}

?>