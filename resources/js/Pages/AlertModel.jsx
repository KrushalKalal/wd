import React from "react";

export default function AlertModal({ show, type, message, fileName, onClose }) {
    if (!show) return null;

    const successIcon = "https://cdn-icons-png.flaticon.com/512/190/190411.png";
    const errorIcon = "https://cdn-icons-png.flaticon.com/512/463/463612.png";

    return (
        <>
            {/* BACKDROP */}
            <div
                style={{
                    position: "fixed",
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    backgroundColor: "rgba(0, 0, 0, 0.5)",
                    zIndex: 9999,
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                }}
                onClick={onClose}
            >
                {/* POPUP CENTER */}
                <div
                    style={{
                        backgroundColor: "white",
                        borderRadius: "12px",
                        padding: "30px",
                        maxWidth: "400px",
                        width: "90%",
                        textAlign: "center",
                        boxShadow: "0 10px 40px rgba(0,0,0,0.2)",
                    }}
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* ICON CIRCLE */}
                    <div
                        style={{
                            width: "80px",
                            height: "80px",
                            borderRadius: "50%",
                            backgroundColor:
                                type === "success" ? "#d4edda" : "#f8d7da",
                            margin: "0 auto 20px",
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "center",
                        }}
                    >
                        <img
                            src={type === "success" ? successIcon : errorIcon}
                            alt={type}
                            style={{ width: "45px", height: "45px" }}
                        />
                    </div>

                    {/* TITLE */}
                    <h4
                        style={{
                            fontSize: "24px",
                            fontWeight: "bold",
                            marginBottom: "15px",
                            color: type === "success" ? "#28a745" : "#dc3545",
                        }}
                    >
                        {type === "success" ? "Success!" : "Error!"}
                    </h4>

                    {/* MESSAGE */}
                    <p
                        style={{
                            fontSize: "16px",
                            color: "#666",
                            marginBottom: "20px",
                            lineHeight: "1.5",
                        }}
                    >
                        {message || ""}
                    </p>

                    {fileName && (
                        <div
                            style={{
                                backgroundColor: "#f8f9fa",
                                padding: "10px",
                                borderRadius: "6px",
                                marginBottom: "20px",
                                fontSize: "14px",
                                color: "#495057",
                            }}
                        >
                            {fileName}
                        </div>
                    )}

                    {/* OK BUTTON */}
                    <button
                        onClick={onClose}
                        style={{
                            backgroundColor: "#212529",
                            color: "white",
                            border: "none",
                            padding: "12px 40px",
                            borderRadius: "6px",
                            fontSize: "16px",
                            fontWeight: "600",
                            cursor: "pointer",
                            transition: "all 0.3s",
                        }}
                        onMouseOver={(e) => {
                            e.target.style.backgroundColor = "#000";
                        }}
                        onMouseOut={(e) => {
                            e.target.style.backgroundColor = "#212529";
                        }}
                    >
                        OK
                    </button>
                </div>
            </div>
        </>
    );
}
