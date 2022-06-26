<?php

session_start();
include_once 'Dbh.php';

class UserAuth extends Dbh{
    private $db;

    public function __construct(){
        $this->db = new Dbh();
    }

    public function validatePassword(string $password, string $confirmPassword){
        return $password === $confirmPassword;
    }

    public function checkEmailExists(string $email){
        $conn = $this->db->connect();
        $check_email_stmt = "SELECT * FROM students WHERE email = :email LIMIT 1";
        $check_email_query = $conn->prepare($check_email_stmt);
        $check_email_query->execute([':email' => $email]);
        $result = $check_email_query->fetchAll(PDO::FETCH_OBJ);
		if(count($result) < 1){
            return false;
        }
        return true;
    }

    public function register($fullname, $email, $password, $confirmPassword, $country, $gender){
        $conn = $this->db->connect();
        if($this->validatePassword($password, $confirmPassword)){
            try{
                $save_user_stmt = "INSERT INTO students (full_names, email, password, country, gender) VALUES (:fullname,:email, :password, :country, :gender)";
                $save_user_query = $conn->prepare($save_user_stmt);
                $save_user_query->execute([
                    ':fullname' => $fullname,
                    ':email' => $email,
                    ':password' => md5($password),
                    ':country' => $country,
                    ':gender' => $gender,
                ]);
                return true;
            } catch (PDOException $exception) {
                die('Oops, something went wrong: '.$exception->getMessage());
            }
        }
        return false;
    }

    public function login($email, $password){
        $conn = $this->db->connect();
        try{
            $login_user_stmt = "SELECT * FROM students WHERE email=:email AND password = :password LIMIT 1";
            $login_user_query = $conn->prepare($login_user_stmt);
            $login_user_query->execute([
                ':email' => $email,
                ':password' => md5($password),
            ]);
            $result = $login_user_query->fetchAll(PDO::FETCH_ASSOC);
            if(count($result) > 0){
                $_SESSION['username'] = $result[0]['full_names'];
                $_SESSION['email'] = $email;
                header("Location: ../dashboard.php");
                exit();
            } else {
                header("Location: forms/login.php");
                exit();
            }
        } catch (PDOException $exception) {
            die('Oops, something went wrong: '.$exception->getMessage());
        }
    }

    public function updateUser(string $email, string $password){
        $conn = $this->db->connect();
        
        if($this->checkEmailExists($email)){
            try{
                $update_user_stmt = "UPDATE students SET password = :password WHERE email = :email";
                $update_user_query = $conn->prepare($update_user_stmt);
                $update_user_query->execute([
                    ':password' => md5($password),
                    ':email' => $email
                ]);
            } catch (PDOException $exception) {
                die('Oops, something went wrong: '.$exception->getMessage());
            }
            // die('Email exists, password has been changed');
            header("Location: forms/login.php");
        } else {
            // die('Email does not exist');
            header("Location: forms/resetpassword.php?error=1");
        }
    }

    public function deleteUser($id){
        $conn = $this->db->connect();

        try{
            $delete_user_stmt = "DELETE FROM students WHERE id = :id";
            $delete_user_query = $conn->prepare($delete_user_stmt);
            $delete_user_query->execute([':id' => $id]);
        } catch (PDOException $exception) {
            header("refresh:0.5; url=action.php?all=?message=Error($exception->getMessage())");
            exit();
            // die('Oops, something went wrong : '.$exception->getMessage());
        }

        header("refresh:0.5; url=action.php?all");
        exit();
    }

    // public function getUser($username){
    //     $conn = $this->db->connect();
    //     $sql = "SELECT * FROM users WHERE username = '$username'";
    //     $result = $conn->query($sql);
    //     if($result->num_rows > 0){
    //         return $result->fetch_assoc();
    //     } else {
    //         return false;
    //     }
    // }

    public function getAllUsers(){
        $conn = $this->db->connect();
        $sql = "SELECT * FROM students";
        $result = $conn->query($sql);
        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        echo"<html>
        <head>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css' integrity='sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T' crossorigin='anonymous'>
        </head>
        <body>
        <center><h1><u> ZURI PHP STUDENTS </u> </h1> 
        <center><p><a href='./dashboard.php'>Go to dashboard</a> </p> 
        <table class='table table-bordered' border='0.5' style='width: 80%; background-color: smoke; border-style: none'; >
        <tr style='height: 40px'>
            <thead class='thead-dark'> <th>ID</th><th>Full Names</th> <th>Email</th> <th>Gender</th> <th>Country</th> <th>Action</th>
        </thead></tr>";
        if(count($result) > 0){
            foreach($result as $data){
                //show data
                echo "<tr style='height: 20px'>".
                    "<td style='width: 50px; background: gray'>" . $data['id'] . "</td>
                    <td style='width: 150px'>" . $data['full_names'] .
                    "</td> <td style='width: 150px'>" . $data['email'] .
                    "</td> <td style='width: 150px'>" . $data['gender'] . 
                    "</td> <td style='width: 150px'>" . $data['country'] . 
                    "</td>
                    <td style='width: 150px'> 
                    <form action='action.php' method='post'>
                    <input type='hidden' name='id'" .
                     "value=" . $data['id'] . ">".
                    "<button class='btn btn-danger' type='submit', name='delete'> DELETE </button> </form> </td>".
                    "</tr>";
            }
            
        } else {
            echo "<tr style='height: 20px'>".
                    "<td colspan='6' style='width: 50px; background: transparent'><center>There are no users !!!</center></td>".
                "</tr>";
        }
        echo "</table></table></center></body></html>";
    }

    

    // public function getUserByUsername($username){
    //     $conn = $this->db->connect();
    //     $sql = "SELECT * FROM users WHERE username = '$username'";
    //     $result = $conn->query($sql);
    //     if($result->num_rows > 0){
    //         return $result->fetch_assoc();
    //     } else {
    //         return false;
    //     }
    // }

    public function logout($username){
        session_start();
        session_destroy();
        header('Location: index.php');
    }

    // public function confirmPasswordMatch($password, $confirmPassword){
    //     if($password === $confirmPassword){
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }
}