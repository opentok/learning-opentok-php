<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Learning OpenTok PHP</title>
</head>

<style>
  p, td {
    font-family: Arial;
  }
  td:first-child {
    font-family: Consolas, Courier, monospace;
    padding-right: 20px
  }
  tr {
    border: 1px solid black
  }
</style>

<body>
    <p>
      This is a sample web service for use with OpenTok. See the OpenTok
      <a href="https://github.com/opentok/learning-opentok-php">
      learning-opentok-php</a> repo on GitHub.
    </p>
    <p>
      Resources are defined at the following endpoints:
    <table>
      <tr>
        <td>GET /session/</td>
        <td>Return an OpenTok API key, session ID, and token.</td>
      </tr>
      <tr>
        <td>POST /start/:sessionId</td>
        <td>Start an archive for the specified OpenTok session.</td>
      </tr>
      <tr>
        <td>POST /stop/:archiveId</td>
        <td>Stop the specified archive.</td>
      </tr>
      <tr>
        <td>GET /view/:archiveId</td>
        <td>View the specified archive.</td>
      </tr>
    </ul>
  </p>
</body>

</html>
