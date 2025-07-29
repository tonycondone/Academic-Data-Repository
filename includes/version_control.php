<?php
/**
 * Version Control System
 * Academic Dataset Collaboration Platform
 */

class VersionControl {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Create a new branch for a file
     */
    public function createBranch($projectId, $branchName, $description, $createdBy, $fromVersionId = null) {
        try {
            // Check if branch name already exists in project
            $query = "SELECT id FROM branches WHERE project_id = :project_id AND name = :name";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':project_id', $projectId);
            $stmt->bindParam(':name', $branchName);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Branch name already exists in this project'
                ];
            }
            
            // Create branch
            $query = "INSERT INTO branches (project_id, name, description, created_from_version, created_by) 
                      VALUES (:project_id, :name, :description, :from_version, :created_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':project_id', $projectId);
            $stmt->bindParam(':name', $branchName);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':from_version', $fromVersionId);
            $stmt->bindParam(':created_by', $createdBy);
            
            if ($stmt->execute()) {
                $branchId = $this->db->lastInsertId();
                
                // Log activity
                logActivity($projectId, $createdBy, 'create_branch', 'branch', $branchId, [
                    'branch_name' => $branchName,
                    'from_version' => $fromVersionId
                ]);
                
                return [
                    'success' => true,
                    'branch_id' => $branchId,
                    'message' => 'Branch created successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create branch'
            ];
            
        } catch (Exception $e) {
            error_log("Create branch error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create branch'
            ];
        }
    }
    
    /**
     * Create a new version of a file
     */
    public function createVersion($fileId, $filePath, $fileSize, $changesDescription, $createdBy, $branchId = null) {
        try {
            // Get current version number
            $query = "SELECT MAX(version_number) as max_version FROM file_versions WHERE file_id = :file_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':file_id', $fileId);
            $stmt->execute();
            $result = $stmt->fetch();
            $newVersion = ($result['max_version'] ?? 0) + 1;
            
            // Create version record
            $query = "INSERT INTO file_versions (file_id, version_number, file_path, file_size, changes_description, created_by, branch_id, checksum) 
                      VALUES (:file_id, :version_number, :file_path, :file_size, :changes_description, :created_by, :branch_id, :checksum)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':file_id', $fileId);
            $stmt->bindParam(':version_number', $newVersion);
            $stmt->bindParam(':file_path', $filePath);
            $stmt->bindParam(':file_size', $fileSize);
            $stmt->bindParam(':changes_description', $changesDescription);
            $stmt->bindParam(':created_by', $createdBy);
            $stmt->bindParam(':branch_id', $branchId);
            $stmt->bindParam(':checksum', hash_file('sha256', $filePath));
            
            if ($stmt->execute()) {
                $versionId = $this->db->lastInsertId();
                
                // Update file's current version
                $query = "UPDATE files SET current_version = :version, updated_at = NOW() WHERE id = :file_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':version', $newVersion);
                $stmt->bindParam(':file_id', $fileId);
                $stmt->execute();
                
                return [
                    'success' => true,
                    'version_id' => $versionId,
                    'version_number' => $newVersion,
                    'message' => 'Version created successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create version'
            ];
            
        } catch (Exception $e) {
            error_log("Create version error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create version'
            ];
        }
    }
    
    /**
     * Merge branches
     */
    public function mergeBranch($sourceBranchId, $targetBranchId, $mergedBy, $mergeMessage = '') {
        try {
            $this->db->beginTransaction();
            
            // Get source branch info
            $query = "SELECT * FROM branches WHERE id = :branch_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':branch_id', $sourceBranchId);
            $stmt->execute();
            $sourceBranch = $stmt->fetch();
            
            if (!$sourceBranch) {
                throw new Exception('Source branch not found');
            }
            
            // Get all versions from source branch that need to be merged
            $query = "SELECT fv.*, f.project_id FROM file_versions fv 
                      JOIN files f ON fv.file_id = f.id 
                      WHERE fv.branch_id = :branch_id AND fv.is_merged = 0";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':branch_id', $sourceBranchId);
            $stmt->execute();
            $versionsToMerge = $stmt->fetchAll();
            
            // Mark versions as merged
            foreach ($versionsToMerge as $version) {
                $query = "UPDATE file_versions SET is_merged = 1 WHERE id = :version_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':version_id', $version['id']);
                $stmt->execute();
                
                // Log merge activity
                logActivity($version['project_id'], $mergedBy, 'merge_version', 'version', $version['id'], [
                    'source_branch' => $sourceBranch['name'],
                    'merge_message' => $mergeMessage
                ]);
            }
            
            // Mark source branch as merged
            $query = "UPDATE branches SET is_merged = 1, merged_at = NOW(), merged_by = :merged_by WHERE id = :branch_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':merged_by', $mergedBy);
            $stmt->bindParam(':branch_id', $sourceBranchId);
            $stmt->execute();
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Branch merged successfully',
                'merged_versions' => count($versionsToMerge)
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Merge branch error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to merge branch: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Rollback to a specific version
     */
    public function rollbackToVersion($fileId, $versionId, $userId) {
        try {
            // Get version info
            $query = "SELECT fv.*, f.project_id FROM file_versions fv 
                      JOIN files f ON fv.file_id = f.id 
                      WHERE fv.id = :version_id AND fv.file_id = :file_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':version_id', $versionId);
            $stmt->bindParam(':file_id', $fileId);
            $stmt->execute();
            $version = $stmt->fetch();
            
            if (!$version) {
                return [
                    'success' => false,
                    'message' => 'Version not found'
                ];
            }
            
            // Copy the version file to current location
            $query = "SELECT file_path FROM files WHERE id = :file_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':file_id', $fileId);
            $stmt->execute();
            $currentFile = $stmt->fetch();
            
            if (file_exists($version['file_path']) && $currentFile) {
                copy($version['file_path'], $currentFile['file_path']);
                
                // Create a new version entry for the rollback
                $result = $this->createVersion(
                    $fileId,
                    $currentFile['file_path'],
                    $version['file_size'],
                    "Rollback to version {$version['version_number']}",
                    $userId
                );
                
                if ($result['success']) {
                    // Log rollback activity
                    logActivity($version['project_id'], $userId, 'rollback', 'file', $fileId, [
                        'rollback_to_version' => $version['version_number'],
                        'new_version' => $result['version_number']
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => "Successfully rolled back to version {$version['version_number']}",
                        'new_version' => $result['version_number']
                    ];
                }
            }
            
            return [
                'success' => false,
                'message' => 'Failed to rollback file'
            ];
            
        } catch (Exception $e) {
            error_log("Rollback error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to rollback file'
            ];
        }
    }
    
    /**
     * Get version history for a file
     */
    public function getVersionHistory($fileId) {
        try {
            $query = "SELECT fv.*, u.first_name, u.last_name, b.name as branch_name
                      FROM file_versions fv
                      JOIN users u ON fv.created_by = u.id
                      LEFT JOIN branches b ON fv.branch_id = b.id
                      WHERE fv.file_id = :file_id
                      ORDER BY fv.version_number DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':file_id', $fileId);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get version history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get branches for a project
     */
    public function getProjectBranches($projectId) {
        try {
            $query = "SELECT b.*, u.first_name, u.last_name,
                             (SELECT COUNT(*) FROM file_versions fv WHERE fv.branch_id = b.id) as version_count
                      FROM branches b
                      JOIN users u ON b.created_by = u.id
                      WHERE b.project_id = :project_id
                      ORDER BY b.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':project_id', $projectId);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Get project branches error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compare two versions of a file
     */
    public function compareVersions($versionId1, $versionId2) {
        try {
            $query = "SELECT * FROM file_versions WHERE id IN (:version1, :version2)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':version1', $versionId1);
            $stmt->bindParam(':version2', $versionId2);
            $stmt->execute();
            $versions = $stmt->fetchAll();
            
            if (count($versions) !== 2) {
                return [
                    'success' => false,
                    'message' => 'One or both versions not found'
                ];
            }
            
            $comparison = [
                'success' => true,
                'version1' => $versions[0],
                'version2' => $versions[1],
                'size_diff' => $versions[1]['file_size'] - $versions[0]['file_size'],
                'checksum_match' => $versions[0]['checksum'] === $versions[1]['checksum']
            ];
            
            // If both files exist, we could add content comparison here
            if (file_exists($versions[0]['file_path']) && file_exists($versions[1]['file_path'])) {
                $comparison['files_exist'] = true;
                // Add file content comparison logic here if needed
            }
            
            return $comparison;
            
        } catch (Exception $e) {
            error_log("Compare versions error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to compare versions'
            ];
        }
    }
}

// Create global version control instance
$versionControl = new VersionControl();
?>