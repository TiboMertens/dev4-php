<?php
class User
{
    protected int $id;
    protected string $username;
    protected string $email;
    protected string $password;
    protected string $verifyToken;


    /**
     * Get the value of id
     */
    public function getId($username)
    {
        $conn = Db::getInstance();
        $statement = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $statement->bindValue(":username", $username);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Get the value of username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of username
     *
     * @return  self
     */
    public function setUsername($username)
    {
        if (!empty($username)) {
            $this->username = $username;
            return $this;
        } else {
            throw new Exception("Not a valid username");
        }
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = $email;
            return $this;
        } else {
            throw new Exception("Not a valid email address");
        }
    }

    /**
     * Get the value of password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the value of password
     *
     * @return  self
     */
    public function setPassword($password)
    {
        //check if password is not empty and at least 10 characters long
        if (!empty($password) && strlen($password) >= 5) {
            //hash password with a factor of 12
            $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

            $this->password = $password;
            return $this;
        } else {
            throw new Exception("Password cannot be empty and must be at least 5 characters long");
        }
    }

    /**
     * Get the value of token
     */
    public function getVerifyToken()
    {
        return $this->verifyToken;
    }

    /**
     * Set the value of token
     *
     * @return  self
     */
    public function setVerifyToken($verifyToken)
    {
        $this->verifyToken = $verifyToken;

        return $this;
    }

    public function sendVerifyEmail()
    {
        $token = $this->verifyToken;

        //prevent XSS
        $username = htmlspecialchars($this->username);

        // send an email to the user
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("r0892926@student.thomasmore.be", "Tibo Mertens");
        $email->setSubject("Verifictation email");
        $email->addTo($this->email, $this->username);
        $email->addContent("text/plain", "Hi $username! Please activate your email. Here is the activation link http://localhost/php/eindwerk/verification.php?token=$token");
        $email->addContent(
            "text/html",
            "Hi $username! Please activate your email. <strong>Here is the activation link:</strong> http://localhost/php/eindwerk/verification.php?token=$token"
        );

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));

        //redirect to index.php with success message
        header("Location:index.php?success=" . urlencode("Activation Email Sent!"));

        try {
            $response = $sendgrid->send($email);
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }

        exit();
    }

    public function save()
    {
        $conn = Db::getInstance();
        $statement = $conn->prepare("insert into users (username, email, password, token) values (:username, :email, :password, :token)");
        $statement->bindValue(":username", $this->username);
        $statement->bindValue(":email", $this->email);
        $statement->bindValue(":password", $this->password);
        $statement->bindValue(":token", $this->verifyToken);
        $result = $statement->execute();
        return $result;
    }

    public function canLogin($username, $password)
    {
        try {
            $conn = Db::getInstance();
            $statement = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $statement->bindValue(":username", $username);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            //check if user exists, if not throw exception
            if (!$user) {
                throw new Exception("Incorrect username or password.");
            }

            $hash = $user['password'];

            //check if password is correct, if not throw exception
            if (password_verify($password, $hash)) {
                return true;
            } else {
                throw new Exception("Incorrect username or password.");
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function isModerator($id)
    {
        $conn = Db::getInstance();
        $statement = $conn->prepare("SELECT is_admin FROM users WHERE id = :id");
        $statement->bindValue(":id", intval($id));
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $result = $result['is_admin'];

        //if result is 1, user is admin, else user is not admin
        if ($result === 1) {
            return true;
        } else {
            return false;
        }
    }
}
